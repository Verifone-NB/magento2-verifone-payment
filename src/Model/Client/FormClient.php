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
     * Form constructor.
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface      $scopeConfig
     * @param \Verifone\Payment\Model\Client\Form\Config              $config
     * @param \Verifone\Payment\Model\Client\Form\Order\DataValidator $dataValidator
     * @param \Verifone\Payment\Model\Client\Form\Order\DataGetter    $dataGetter
     * @param \Verifone\Payment\Model\Session                         $session
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Verifone\Payment\Model\Client\Form\Config $config,
        \Verifone\Payment\Model\Client\Form\Order\DataValidator $dataValidator,
        \Verifone\Payment\Model\Client\Form\Order\DataGetter $dataGetter,
        \Verifone\Payment\Model\Session $session
    ) {
        parent::__construct($scopeConfig, $config);

        $this->_dataValidator = $dataValidator;
        $this->_dataGetter = $dataGetter;
        $this->_session = $session;

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
            $config['software-version'],
            $config['skip-confirmation']
        );

        $order = new OrderImpl(
            (string)$data['order_id'],
            $data['time'],
            (string)$data['currency_code'],
            (string)$data['total_incl_amount'],
            (string)$data['total_excl_amount'],
            (string)$data['total_vat']
        );

        $customer = new CustomerImpl(
            (string)$data['customer']['firstname'],
            (string)$data['customer']['lastname'],
            (string)$data['customer']['phone'],
            (string)$data['customer']['email']
        );

        $products = [];

        foreach ($data['products'] as $product) {
            $products[] = new ProductImpl(
                (string)$product['name'],
                (string)$product['unit_cost'],
                (string)$product['net_amount'],
                (string)$product['gross_amount'],
                (string)$product['unit_count'],
                (string)$product['discount_percentage']
            );
        }

        $paymentInfo = new PaymentInfoImpl('fi_FI', '');

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

        $form = $exec->executeService($service, $config['payment-url']);

        $this->_session->setOrderCreateData($form);

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
     * @param array                      $requestData
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
        $paymentMethods = explode(',', $this->_scopeConfig->getValue(Path::XML_PATH_PAYMENT_METHODS));
        $cardMethods = explode(',', $this->_scopeConfig->getValue(Path::XML_PATH_CARD_METHODS));

        return [
            'card' => $cardMethods,
            'bank' => $paymentMethods
        ];
    }
}