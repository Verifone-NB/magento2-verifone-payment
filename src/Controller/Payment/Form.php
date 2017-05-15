<?php
/**
 *
 * NOTICE OF LICENSE
 *
 * This source file is released under commercial license by Lamia Oy.
 *
 * @copyright Copyright (c) 2017 Lamia Oy (https://lamia.fi)
 * @author    Szymon Nosal <simon@lamia.fi>
 */

namespace Verifone\Payment\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Verifone\Payment\Model\Order\Exception;

class Form extends Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $_resultPageFactory;

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
     * @var \Verifone\Payment\Logger\VerifoneLogger
     */
    protected $_logger;

    /**
     * @var \Verifone\Payment\Model\Sales\Order\Config
     */
    protected $_orderConfig;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Verifone\Payment\Model\Session       $session
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Verifone\Payment\Model\Session $session,
        \Verifone\Payment\Model\ClientFactory $clientFactory,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Verifone\Payment\Helper\Order $orderHelper,
        \Verifone\Payment\Logger\VerifoneLogger $logger,
        \Verifone\Payment\Model\Sales\Order\Config $orderConfig
    ) {
        parent::__construct($context);
        $this->_session = $session;
        $this->_clientFactory = $clientFactory;
        $this->_resultPageFactory = $resultPageFactory;
        $this->_orderHelper = $orderHelper;
        $this->_logger = $logger;
        $this->_orderConfig = $orderConfig;
    }

    /**
     * @return \Magento\Framework\Controller\Result\Redirect|\Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        /**
         * @var $resultRedirect \Magento\Framework\Controller\Result\Redirect
         * @var $resultPage     \Magento\Framework\View\Result\Page
         * @var $resultRedirect    \Magento\Framework\Controller\Result\Redirect
         */
        $resultRedirect = $this->resultRedirectFactory->create();
        $redirectUrl = 'checkout/cart';
        $redirectParams = [];
        $orderId = $this->_orderHelper->getOrderIdForPayment();

        if ($orderId) {
            $order = $this->_orderHelper->loadOrderById($orderId);
            try {
                $paymentMethod = $this->_session->getPaymentMethod();

                /** @var \Verifone\Payment\Model\Client\FormClient $client */
                $client = $this->_clientFactory->create('frontend');
                $orderData = $client->getDataForOrderCreate($order);

                $orderCreateData = $client->orderCreate($orderData);

                $resultPage = $this->_resultPageFactory->create(true,
                    ['template' => 'Verifone_Payment::emptyroot.phtml']);

                $resultPage->addHandle($resultPage->getDefaultLayoutHandle());
                $resultPage->getLayout()->getBlock('verifone.payment.form')->setCreateData($orderCreateData);

                $order
                    ->setState(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT)
                    ->addStatusToHistory($this->_orderConfig->getStateDefaultStatus(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT));

                $order->setData('payment_method_code', $paymentMethod);

                $order->getResource()->save($order);

                $this->_session->setPaymentMethod(null);
                $this->_session->setPaymentMethodId(null);

                return $resultPage;

            } catch (Exception $e) {
                $this->_logger->critical($e);
                $redirectUrl = 'verifone_payment/payment/cancel';
                $redirectParams = ['exception' => '1'];
            }
        }

        $resultRedirect->setPath($redirectUrl, $redirectParams);
        return $resultRedirect;

    }
}