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

namespace Verifone\Payment\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;

class Test extends Action
{

    /**
     * @var \Verifone\Payment\Model\Db\Payment\MethodFactory
     */
    protected $_methodFactory;

    /**
     * @var \Verifone\Payment\Model\ClientFactory
     */
    protected $_clientFactory;

    /**
     * @var \Verifone\Payment\Model\Session
     */
    protected $_session;

    /**
     * @var \Verifone\Payment\Helper\Order
     */
    protected $_orderHelper;

    /**
     * @var \Verifone\Payment\Model\Sales\Order\Config
     */
    protected $_orderConfig;

    /**
     * @var \Verifone\Payment\Model\Order\PaymentMethod
     */
    protected $_payentMethodHelper;


    public function __construct(
        Context $context,
        \Verifone\Payment\Model\Db\Payment\MethodFactory $methodFactory,
        \Verifone\Payment\Model\Session $session,
        \Verifone\Payment\Model\ClientFactory $clientFactory,
        \Verifone\Payment\Helper\Order $orderHelper,
        \Verifone\Payment\Model\Sales\Order\Config $orderConfig,
        \Verifone\Payment\Model\Order\PaymentMethod $paymentMethod

    ) {
        parent::__construct($context);
        $this->_methodFactory = $methodFactory;
        $this->_session = $session;
        $this->_clientFactory = $clientFactory;
        $this->_orderHelper = $orderHelper;
        $this->_orderConfig = $orderConfig;
        $this->_payentMethodHelper = $paymentMethod;
    }

    public function execute()
    {
        echo '<pre>';

        /** @var \Verifone\Payment\Model\Client\RestClient $client */
        $client = $this->_clientFactory->create('backend');

        $cards = $client->getListSavedPaymentMethods();
        var_dump($cards);

        die();

        //var_dump($this->_orderConfig->getStateDefaultStatus(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT));

//        $this->_payentMethodHelper->getPaymentMethods();

//        die();

        //
        //var_dump($model->getActiveMethods()->getSize() > 0);die();
        //
        //$collection = $model->getCollection();
        //$obj = $model->loadByCode('handelsbanken-se-account');
        //var_dump($obj->getData());
        //
        /////** @var  \Verifone\Payment\Model\Db\Payment\Method $item */
        //foreach ($collection as $item) {
        //    var_dump($item->getData());
        //}

        $orderId = 37;

        $this->_session->setPaymentMethod('invoice-collector');
        $this->_session->setSavePaymentMethod(true);

        if ($orderId) {
            $order = $this->_orderHelper->loadOrderById($orderId);

            /** @var \Verifone\Payment\Model\Client\FormClient $client */
            $client = $this->_clientFactory->create('frontend');
            $orderData = $client->getDataForOrderCreate($order);

            $orderCreateData = $client->orderCreate($orderData);

            var_dump($orderCreateData);die('qqqqqqqqqq');
        }
    }
}