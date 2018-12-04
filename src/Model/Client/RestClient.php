<?php
/**
 *
 * NOTICE OF LICENSE
 *
 * This source file is released under commercial license by Lamia Oy.
 *
 * @copyright  Copyright (c) 2017 Lamia Oy (https://lamia.fi)
 * @author     Szymon Nosal <simon@lamia.fi>
 *
 */

namespace Verifone\Payment\Model\Client;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Verifone\Core\DependencyInjection\Configuration\Backend\BackendConfigurationImpl;
use Verifone\Core\DependencyInjection\Configuration\Backend\GetAvailablePaymentMethodsConfigurationImpl;
use Verifone\Core\DependencyInjection\CoreResponse\PaymentStatusImpl;
use Verifone\Core\DependencyInjection\Service\AddressImpl;
use Verifone\Core\DependencyInjection\Service\CustomerImpl;
use Verifone\Core\DependencyInjection\Service\OrderImpl;
use Verifone\Core\DependencyInjection\Service\PaymentInfoImpl;
use Verifone\Core\DependencyInjection\Service\TransactionImpl;
use Verifone\Core\DependencyInjection\Transporter\CoreResponse;
use Verifone\Core\Executor\BackendServiceExecutor;
use Verifone\Core\ExecutorContainer;
use Verifone\Core\Service\Backend\GetPaymentStatusService;
use Verifone\Core\Service\Backend\GetSavedCreditCardsService;
use Verifone\Core\Service\Backend\ListTransactionNumbersService;
use Verifone\Core\Service\Backend\ProcessPaymentService;
use Verifone\Core\Service\Backend\RefundPaymentService;
use Verifone\Core\ServiceFactory;
use Verifone\Payment\Model\Order\Exception;

class RestClient extends \Verifone\Payment\Model\Client
{

    /**
     * @var \Verifone\Payment\Model\Client\Rest\Order\DataValidator
     */
    protected $_dataValidator;

    /**
     * @var \Verifone\Payment\Model\Client\Rest\Order\DataGetter
     */
    protected $_dataGetter;

    /**
     * @var \Verifone\Payment\Model\Session
     */
    protected $_session;

    /**
     * @var \Verifone\Payment\Model\Client\Rest\Config
     */
    protected $_config;

    /**
     * @var \Verifone\Payment\Helper\Payment
     */
    protected $_paymentHelper;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        Rest\Config $config,
        \Verifone\Payment\Model\Client\Rest\Order\DataValidator $dataValidator,
        \Verifone\Payment\Model\Client\Rest\Order\DataGetter $dataGetter,
        \Verifone\Payment\Model\Session $session,
        \Verifone\Payment\Helper\Payment $paymentHelper
    )
    {
        parent::__construct($scopeConfig, $config);

        $this->_dataValidator = $dataValidator;
        $this->_dataGetter = $dataGetter;
        $this->_session = $session;
        $this->_paymentHelper = $paymentHelper;


        $this->_config->prepareConfig();
    }

    protected function _getExecutor()
    {

    }

    public function orderRefund($order, $payment, $amount)
    {
        $result = $this->refund($order, $payment, $amount);

        if (!$result) {
            throw new LocalizedException(new Phrase('There was a problem while processing order refund request.'));
        }

        return $result;
    }

    /**
     * @param null $merchant
     * @param null $publicKey
     * @param null $privateKey
     *
     * @return array|null
     */
    public function getPaymentMethods($merchant = null, $privateKey = null, $publicKey = null)/*: ?array*/
    {
        $config = $this->_config->getConfig();

        if (null === $merchant) {
            $merchant = $config['merchant'];
        }

        if (null === $publicKey) {
            $publicKey = $config['public-key'];
        }

        if (null === $privateKey) {
            $privateKey = $config['private-key'];
        }

        $publicKeyFile = $this->_config->getFileContent($publicKey);
        $privateKeyFile = $this->_config->getFileContent($privateKey);

        $configObject = new GetAvailablePaymentMethodsConfigurationImpl(
            $privateKeyFile,
            $merchant,
            $config['software'],
            $config['software-version'],
            $config['server-url'],
            $config['currency'],
            $config['rsa-blinding']
        );

        $service = ServiceFactory::createService($configObject, 'Backend\GetAvailablePaymentMethodsService');
        $container = new ExecutorContainer();

        /** @var BackendServiceExecutor $exec */
        $exec = $container->getExecutor('backend');
        $response = $exec->executeService($service, $publicKeyFile);

        if (!$response->getStatusCode()) {
            return null;
        }

        $body = $response->getBody();
        $methods = [];

        foreach ($body as $item) {
            $methods[] = $item->getCode();
        }

        return $methods;
    }

    public function refund(
        \Magento\Sales\Model\Order $order,
        \Magento\Payment\Model\InfoInterface $payment,
        $amount
    )
    {
        $config = $this->_config->getConfig();

        $publicKeyFile = $this->_config->getFileContent($config['public-key']);
        $privateKeyFile = $this->_config->getFileContent($config['private-key']);

        $configObject = $this->_createConfigObject($config, $privateKeyFile);

        $refundAmount = $amount * 100;

        $paymentMethod = $payment->getAdditionalInformation('payment-method');
        if($paymentMethod === null) {
            $additionalInformation = $payment->getAdditionalInformation();
            if(isset($additionalInformation['raw_details_info']['payment-method'])) {
                $paymentMethod = $additionalInformation['raw_details_info']['payment-method'];
            }
        }

        $transaction = new TransactionImpl(
            $paymentMethod,
            $order->getExtOrderId(),
            (string)$refundAmount,
            $config['currency']
        );

        try {
            /** @var RefundPaymentService $service */
            $service = ServiceFactory::createService($configObject, 'Backend\RefundPaymentService');
            $service->insertTransaction($transaction);
            $service->insertRefundProduct($transaction);

            $container = new ExecutorContainer();

            /** @var BackendServiceExecutor $exec */

            $exec = $container->getExecutor('backend');
            $response = $exec->executeService($service, $publicKeyFile);

            if ($response->getStatusCode()) {
                return true;
            }
        } catch (\Exception $e) {
            throw $e;
        }

        return false;

    }

    /**
     * @return CoreResponse
     * @throws \Exception
     */
    public function getListSavedPaymentMethods()
    {
        $customerData = $this->_dataGetter->getCustomerData();

        if (is_null($customerData)) {
            return null;
        }

        $config = $this->_config->getConfig();

        $publicKeyFile = $this->_config->getFileContent($config['public-key']);
        $privateKeyFile = $this->_config->getFileContent($config['private-key']);

        $configObject = $this->_createConfigObject($config, $privateKeyFile);

        $customer = $this->_createCustomerObject($customerData);

        try {
            /**
             * @var GetSavedCreditCardsService $service
             */
            $service = ServiceFactory::createService($configObject, 'Backend\GetSavedCreditCardsService');
            $service->insertCustomer($customer);

            $container = new ExecutorContainer();

            /** @var BackendServiceExecutor $exec */
            $exec = $container->getExecutor('backend');
            $response = $exec->executeService($service, $publicKeyFile);

            return $response;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param string $gateId
     * @return CoreResponse
     * @throws \Exception
     */
    public function removeSavedPaymentMethod(string $gateId)
    {
        $customerData = $this->_dataGetter->getCustomerData();

        if (is_null($customerData)) {
            return null;
        }

        $config = $this->_config->getConfig();

        $publicKeyFile = $this->_config->getFileContent($config['public-key']);
        $privateKeyFile = $this->_config->getFileContent($config['private-key']);

        $configObject = $this->_createConfigObject($config, $privateKeyFile);
        $customer = $this->_createCustomerObject($customerData);

        $payment = new PaymentInfoImpl('', '', $gateId);


        try {
            /**
             * @var GetSavedCreditCardsService $service
             */
            $service = ServiceFactory::createService($configObject, 'Backend\RemoveSavedCreditCardsService');
            $service->insertCustomer($customer);
            $service->insertPaymentInfo($payment);

            $container = new ExecutorContainer();

            /** @var BackendServiceExecutor $exec */
            $exec = $container->getExecutor('backend');
            $response = $exec->executeService($service, $publicKeyFile);

            return $response;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return CoreResponse|null
     */
    public function processPayment(\Magento\Sales\Model\Order $order)
    {
        $data = $this->_dataGetter->getOrderData($order);
        $customerData = $this->_dataGetter->getCustomerData($order);

        if (is_null($customerData) || !$this->_session->getPaymentMethodId()) {
            return null;
        }

        $config = $this->_config->getConfig();

        $publicKeyFile = $this->_config->getFileContent($config['public-key']);
        $privateKeyFile = $this->_config->getFileContent($config['private-key']);

        $configObject = $this->_createConfigObject($config, $privateKeyFile);

        $orderImpl = new OrderImpl(
            (string)$data['order_id'],
            $data['time'],
            (string)$data['currency_code'],
            (string)$data['total_incl_amount'],
            (string)$data['total_excl_amount'],
            (string)$data['total_vat']
        );

        $address = $this->_createAddressObject($this->_dataGetter->getAddressData($order));
        $customer = $this->_createCustomerObject($customerData, $address);

        $paymentInfo = new PaymentInfoImpl(
            $data['locale'],
            '',
            $this->_session->getPaymentMethodId() ? $this->_session->getPaymentMethodId() : '',
            '',
            (bool)$config['save-masked-pan']
        );

        $paymentMethod = '';
        if (isset($data['payment_method'])) {
            $paymentMethod = $data['payment_method'];
        }

        $transactionInfo = new TransactionImpl(
            $paymentMethod,
            !is_null($data['ext_order_id']) ? $data['ext_order_id'] : '');

        /** @var ProcessPaymentService $service */
        $service = ServiceFactory::createService($configObject, 'Backend\ProcessPaymentService');
        $service->insertCustomer($customer);
        $service->insertOrder($orderImpl);
        $service->insertPaymentInfo($paymentInfo);
        $service->insertTransaction($transactionInfo);

        $container = new ExecutorContainer();

        /** @var BackendServiceExecutor $exec */
        $exec = $container->getExecutor('backend');

        /** @var CoreResponse $response */
        $response = $exec->executeService($service, $publicKeyFile);

        if ($response->getStatusCode()) {
            return $response->getBody();
        } else {
            return null;
        }
    }

    /**
     * @param string $paymentMethod
     * @param string $transactionNumber
     * @return PaymentStatusImpl|null
     */
    public function getPaymentStatus(string $paymentMethod, string $transactionNumber)
    {

        if ($paymentMethod == '' || $transactionNumber == '') {
            return null;
        }

        $config = $this->_config->getConfig();

        $publicKeyFile = $this->_config->getFileContent($config['public-key']);
        $privateKeyFile = $this->_config->getFileContent($config['private-key']);


        $configObject = $this->_createConfigObject($config, $privateKeyFile);

        $transaction = new TransactionImpl($paymentMethod, $transactionNumber);

        /** @var GetPaymentStatusService $service */
        $service = ServiceFactory::createService($configObject, 'Backend\GetPaymentStatusService');
        $service->insertTransaction($transaction);

        $container = new ExecutorContainer();

        /** @var BackendServiceExecutor $exec */
        $exec = $container->getExecutor('backend');

        /** @var CoreResponse $response */
        $response = $exec->executeService($service, $publicKeyFile);

        if ($response->getStatusCode()) {
            return $response->getBody();
        } else {
            return null;
        }
    }

    public function getTransactionsFromGate($orderIncrementId)
    {
        $config = $this->_config->getConfig();

        $publicKeyFile = $this->_config->getFileContent($config['public-key']);
        $privateKeyFile = $this->_config->getFileContent($config['private-key']);

        $configObject = $this->_createConfigObject($config, $privateKeyFile);

        $order = new OrderImpl($orderIncrementId, '', '', '', '', '', '');

        /** @var ListTransactionNumbersService $service */
        $service = ServiceFactory::createService($configObject, 'Backend\ListTransactionNumbersService');
        $service->insertOrder($order);

        $container = new ExecutorContainer();

        /** @var BackendServiceExecutor $exec */
        $exec = $container->getExecutor('backend');

        /** @var CoreResponse $response */
        $response = $exec->executeService($service, $publicKeyFile);

        if ($response->getStatusCode()) {
            return $response->getBody();
        } else {
            return null;
        }
    }

    protected function _createConfigObject($config, $privateKeyFile, $merchant = null)
    {
        return new BackendConfigurationImpl(
            $privateKeyFile,
            !is_null($merchant) ? $merchant : $config['merchant'],
            $config['software'],
            $config['software-version'],
            $config['server-url'],
            $config['rsa-blinding']
        );
    }

    /**
     * @param array $customerData
     * @param AddressImpl $address
     * @return CustomerImpl
     */
    protected function _createCustomerObject($customerData, $address = null)
    {
        return new CustomerImpl(
            $this->_paymentHelper->sanitize($customerData['firstname']),
            $this->_paymentHelper->sanitize($customerData['lastname']),
            $this->_paymentHelper->sanitize($customerData['phone']),
            $this->_paymentHelper->sanitize($customerData['email']),
            isset($customerData['external_id']) && $customerData['external_id'] ? (string)$customerData['external_id'] : '',
            $address
        );
    }

    protected function _createAddressObject($addressData)
    {
        return new AddressImpl(
            $this->_paymentHelper->sanitize($addressData['line-1']),
            $this->_paymentHelper->sanitize($addressData['line-2']),
            $this->_paymentHelper->sanitize($addressData['line-3']),
            $this->_paymentHelper->sanitize($addressData['city']),
            $this->_paymentHelper->sanitize($addressData['postal-code']),
            $this->_paymentHelper->sanitize($addressData['country-code'])
        );
    }

}