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
use Verifone\Core\DependencyInjection\Configuration\Frontend\FrontendConfigurationImpl;
use Verifone\Core\DependencyInjection\CoreResponse\PaymentResponseImpl;
use Verifone\Core\DependencyInjection\Service\CustomerImpl;
use Verifone\Core\DependencyInjection\Service\OrderImpl;
use Verifone\Core\DependencyInjection\Service\PaymentInfoImpl;
use Verifone\Core\DependencyInjection\Service\ProductImpl;
use Verifone\Core\DependencyInjection\Service\TransactionImpl;
use Verifone\Core\DependencyInjection\Transporter\CoreResponse;
use Verifone\Core\Exception\ResponseCheckFailedException;
use Verifone\Core\ExecutorContainer;
use Verifone\Core\Service\Frontend\CreateNewOrderService;
use Verifone\Core\Service\FrontendResponse\FrontendResponseService;
use Verifone\Core\ServiceFactory;
use Verifone\Payment\Helper\Path;

class FormClient extends \Verifone\Payment\Model\Client
{
    /**
     * @var \Verifone\Payment\Model\Client\Form\Order\DataValidator
     */
    protected $_dataValidator;

    /**
     * @var \Verifone\Payment\Model\Client\Form\Order\DataGetter
     */
    protected $_dataGetter;

    /**
     * @var \Verifone\Payment\Model\Session
     */
    protected $_session;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $_eventManager;

    /**
     * @var \Verifone\Payment\Helper\Payment
     */
    protected $_paymentHelper;

    /**
     * Form constructor.
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Verifone\Payment\Model\Client\Form\Config $config
     * @param \Verifone\Payment\Model\Client\Form\Order\DataValidator $dataValidator
     * @param \Verifone\Payment\Model\Client\Form\Order\DataGetter $dataGetter
     * @param \Verifone\Payment\Model\Session $session
     * @param \Verifone\Payment\Helper\Payment $paymentHelper
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Verifone\Payment\Model\Client\Form\Config $config,
        \Verifone\Payment\Model\Client\Form\Order\DataValidator $dataValidator,
        \Verifone\Payment\Model\Client\Form\Order\DataGetter $dataGetter,
        \Verifone\Payment\Model\Session $session,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Verifone\Payment\Helper\Payment $paymentHelper
    )
    {
        parent::__construct($scopeConfig, $config);

        $this->_dataValidator = $dataValidator;
        $this->_dataGetter = $dataGetter;
        $this->_session = $session;
        $this->_eventManager = $eventManager;
        $this->_paymentHelper = $paymentHelper;

        $this->_config->prepareConfig();
    }

    public function orderCreate(array $data = [])
    {
        if (!$this->validateCreate($data)) {
            throw new LocalizedException(new Phrase('Order request data array is invalid.'));
        }

        $result = $this->createOrder($data);

        if (!$result) {
            throw new LocalizedException(new Phrase('There was a problem while processing order create request.'));
        }
        return $result;
    }

    public function validateCreate(array $data = [])
    {
        return
            $this->_dataValidator->validateEmpty($data) &&
            $this->_dataValidator->validateBasicData($data) &&
            $this->_dataValidator->validateProductsData($data);
    }

    /**
     * @inheritDoc
     */
    public function getDataForOrderCreate(\Magento\Sales\Model\Order $order)
    {
        $data = $this->_dataGetter->getOrderData($order);
        $data['products'] = $this->_dataGetter->getProductsData($order);

        $customerData = $this->_dataGetter->getCustomerData($order);
        if ($customerData) {
            $data['customer'] = $customerData;
        }

        return $data;
    }

    public function createOrder($data)
    {
        $config = $this->_config->getConfig();

        $publicKey = $config['public-key'];
        $privateKey = $config['private-key'];

        $privateKeyFile = $this->_config->getFileContent($privateKey);

        $urls = $this->_config->getRedirectUrlsObject();

        $configObject = new FrontendConfigurationImpl(
            $urls,
            $privateKeyFile,
            $config['merchant'],
            $config['software'],
            $this->_paymentHelper->sanitize($config['software-version']),
            $config['skip-confirmation'],
            $config['rsa-blinding']
        );

        $order = new OrderImpl(
            (string)$data['order_id'],
            $data['time'],
            (string)$data['currency_code'],
            (string)$data['total_incl_amount'],
            (string)$data['total_excl_amount'],
            (string)$data['total_vat']
        );

        $customer = $this->_createCustomerObject($data['customer']);

        $products = [];

        foreach ($data['products'] as $product) {
            $products[] = new ProductImpl(
                $this->_paymentHelper->sanitize($product['name']),
                (string)$product['unit_cost'],
                (string)$product['net_amount'],
                (string)$product['gross_amount'],
                (string)$product['unit_count'],
                (string)$product['discount_percentage']
            );
        }

        $savePaymentMethod = PaymentInfoImpl::SAVE_METHOD_AUTO_NO_SAVE;
        if (isset($data['save_payment_method']) && $data['save_payment_method'] == true) {
            $savePaymentMethod = PaymentInfoImpl::SAVE_METHOD_AUTO_SAVE;
        }

        $paymentMethodId = '';
        if (isset($data['payment_method_id'])) {
            $paymentMethodId = $data['payment_method_id'];
        }

        $paymentInfo = new PaymentInfoImpl(
            $data['locale'],
            $savePaymentMethod,
            $paymentMethodId,
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

        /** @var CreateNewOrderService $service */
        $service = ServiceFactory::createService($configObject, 'Frontend\CreateNewOrderService');
        $service->insertCustomer($customer);
        $service->insertOrder($order);
        $service->insertPaymentInfo($paymentInfo);
        $service->insertTransaction($transactionInfo);

        foreach ($products as $product) {
            $service->insertProduct($product);
        }

        // for json: new ExecutorContainer(array('requestConversion.class' => ExecutorContainer::REQUEST_CONVERTER_TYPE_JSON));
        $container = new ExecutorContainer();
        $exec = $container->getExecutor(ExecutorContainer::EXECUTOR_TYPE_FRONTEND);

        $form = $exec->executeService($service, $config['payment-url'], $config['check-node-availability']);

        $this->_session->setOrderCreateData($form);

        $this->_eventManager->dispatch('verifone_paymentinterface_send_request_before', [
            '_requestData' => $form
        ]);

        return $form;
    }

    /**
     * @param array $requestData
     *
     * @return string
     */
    public function getOrderNumber(array $requestData)
    {

        $config = $this->_config->getConfig();

        /** @var FrontendResponseService $service */
        $service = ServiceFactory::createResponseService($requestData);

        return $service->getOrderNumber();

    }

    /**
     * @param array $requestData
     * @param \Magento\Sales\Model\Order $order
     *
     * @return CoreResponse
     * @throws ResponseCheckFailedException
     */
    public function validateAndParseResponse(array $requestData, \Magento\Sales\Model\Order $order)
    {
        $data = $this->_dataGetter->getOrderData($order);

        $config = $this->_config->getConfig();
        $publicKey = $config['public-key'];

        $publicKeyFile = $this->_config->getFileContent($publicKey);

        $orderImpl = new OrderImpl(
            (string)$data['order_id'],
            $data['time'],
            (string)$data['currency_code'],
            (string)$data['total_incl_amount'],
            (string)$data['total_excl_amount'],
            (string)$data['total_vat']
        );

        /** @var FrontendResponseService $service */
        $service = ServiceFactory::createResponseService($requestData);
        $service->insertOrder($orderImpl);
        $container = new ExecutorContainer(array('responseConversion.class' => 'Converter\Response\FrontendServiceResponseConverter'));
        $exec = $container->getExecutor(ExecutorContainer::EXECUTOR_TYPE_FRONTEND_RESPONSE);

        /** @var CoreResponse $parseResponse */
        $parseResponse = $exec->executeService($service, $publicKeyFile);

        return $parseResponse;
    }

    /**
     * Returns available Payment Methods
     *
     * @return array
     */
    public function getPaymentMethods()
    {
        $paymentMethods = $this->_scopeConfig->getValue(Path::XML_PATH_PAYMENT_METHODS);
        $cardMethods = $this->_scopeConfig->getValue(Path::XML_PATH_CARD_METHODS);

        $groups = $this->_parseGroups($paymentMethods);
        $groups = array_merge($groups, $this->_parseGroups($cardMethods, true));

        usort($groups, array("self", "sortGroups"));

        return $groups;
    }

    /**
     * @param $string
     * @return array
     */
    protected function _parseGroups($string, $isCard = false)
    {
        $groups = unserialize($string);

        $parsed = [];

        foreach ($groups as $group) {
            $parsed[] = $this->_parseGroup($group, $isCard);
        }

        return $parsed;

    }

    /**
     * @param $group
     * @return array
     */
    protected function _parseGroup($group, $isCard = false)
    {
        $isGroup = false;
        $name = isset($group['group_name']) ? $group['group_name'] : '';
        if (strlen($name) && $this->_scopeConfig->getValue(Path::XML_PATH_PAYMENT_DEFAULT_GROUP) != $name) {
            $isGroup = true;
        }

        $description = __('You will be redirected to Verifone to complete your order.');
        if (isset($group['group_description']) && strlen($group['group_description'])) {
            $description = $group['group_description'];
        }

        return [
            'isGroup' => $isGroup,
            'position' => isset($group['position']) ? $group['position'] : 0,
            'name' => $name,
            'description' => $description,
            'isCard' => $isCard,
            'payments' => $group['payments']
        ];
    }

    /**
     * @param $group1
     * @param $group2
     * @return int
     */
    static function sortGroups($group1, $group2)
    {
        if ($group1['position'] == $group2['position']) {
            return 0;
        }

        return ($group1['position'] < $group2['position']) ? -1 : 1;
    }

    public function createCardRequest()
    {
        $customerData = $this->_dataGetter->getCustomerData();

        if (is_null($customerData)) {
            return null;
        }

        $config = $this->_config->getConfig();

        $privateKeyFile = $this->_config->getFileContent($config['private-key']);

        $urls = $this->_config->getRedirectCardUrlsObject();

        $configObject = new FrontendConfigurationImpl(
            $urls,
            $privateKeyFile,
            $config['merchant'],
            $config['software'],
            $this->_paymentHelper->sanitize($config['software-version']),
            $config['skip-confirmation'],
            $config['rsa-blinding']
        );

        $customer = $this->_createCustomerObject($customerData);

        $order = new OrderImpl(
            'addNewCard',
            gmdate('Y-m-d H:i:s'),
            $config['currency'],
            '1',
            '1',
            '0'
        );

        $payment = new PaymentInfoImpl($customerData['locale'], PaymentInfoImpl::SAVE_METHOD_SAVE_ONLY, '', (string)time());

        $service = ServiceFactory::createService($configObject, 'Frontend\AddNewCardService');
        $service->insertCustomer($customer);
        $service->insertOrder($order);
        $service->insertPaymentInfo($payment);

        // for json: new ExecutorContainer(array('requestConversion.class' => ExecutorContainer::REQUEST_CONVERTER_TYPE_JSON));
        $container = new ExecutorContainer();
        $exec = $container->getExecutor(ExecutorContainer::EXECUTOR_TYPE_FRONTEND);

        $form = $exec->executeService($service, $config['payment-url']);

        $this->_eventManager->dispatch('verifone_paymentinterface_send_request_before', [
            '_requestData' => $form
        ]);

        return $form;
    }

    protected function _createCustomerObject($customerData)
    {
        return new CustomerImpl(
            $this->_paymentHelper->sanitize($customerData['firstname']),
            $this->_paymentHelper->sanitize($customerData['lastname']),
            $this->_paymentHelper->sanitize($customerData['phone']),
            $this->_paymentHelper->sanitize($customerData['email']),
            isset($customerData['external_id']) && $customerData['external_id'] ? (string)$customerData['external_id'] : ''
        );
    }
}