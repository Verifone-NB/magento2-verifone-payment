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
use Verifone\Core\DependencyInjection\CoreResponse\PaymentResponseImpl;
use Verifone\Payment\Model\Order\Exception;

class Capture extends Action
{

    /**
     * @var \Magento\Checkout\Model\Session\SuccessValidator
     */
    protected $_successValidator;

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
     * @var \Verifone\Payment\Helper\Payment
     */
    protected $_paymentHelper;

    /**
     * Capture constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Checkout\Model\Session\SuccessValidator $successValidator
     * @param \Verifone\Payment\Model\Session $session
     * @param \Verifone\Payment\Model\ClientFactory $clientFactory
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Verifone\Payment\Helper\Order $orderHelper
     * @param \Verifone\Payment\Logger\VerifoneLogger $logger
     * @param \Verifone\Payment\Model\Sales\Order\Config $orderConfig
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session\SuccessValidator $successValidator,
        \Verifone\Payment\Model\Session $session,
        \Verifone\Payment\Model\ClientFactory $clientFactory,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Verifone\Payment\Helper\Payment $paymentHelper,
        \Verifone\Payment\Logger\VerifoneLogger $logger,
        \Verifone\Payment\Model\Sales\Order\Config $orderConfig,
        \Verifone\Payment\Helper\Order $orderHelper
    )
    {
        parent::__construct($context);

        $this->_successValidator = $successValidator;
        $this->_session = $session;
        $this->_clientFactory = $clientFactory;
        $this->_resultPageFactory = $resultPageFactory;
        $this->_orderHelper = $orderHelper;
        $this->_logger = $logger;
        $this->_orderConfig = $orderConfig;
        $this->_paymentHelper = $paymentHelper;
    }

    public function execute()
    {
        /**
         * @var $resultRedirect \Magento\Framework\Controller\Result\Redirect
         */
        $resultRedirect = $this->resultRedirectFactory->create();
        $redirectParams = [];
        $redirectUrl = 'verifone_payment/payment/form';

        $orderId = $this->_orderHelper->getOrderIdForPayment();

        if ($orderId && $this->_successValidator->isValid()) {

            $resultRedirect = $this->resultRedirectFactory->create();
            $order = $this->_orderHelper->loadOrderById($orderId);

            if (
                $this->_session->getPaymentMethodId() &&
                $this->_paymentHelper->getSavedCardsS2sPaymentLimit() > $order->getGrandTotal()
            ) {

                try {

                    $paymentMethod = $this->_session->getPaymentMethod();

                    $order
                        ->setState(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT)
                        ->addStatusToHistory($this->_orderConfig->getStateDefaultStatus(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT));
                    $order->setData('payment_method_code', $paymentMethod);
                    $order->getResource()->save($order);

                    /** @var \Verifone\Payment\Model\Client\RestClient $client */
                    $client = $this->_clientFactory->create('backend');

                    /** @var PaymentResponseImpl|null $response */
                    $response = $client->processPayment($order);

                    if (
                        !is_null($response) &&
                        $response->getTransactionNumber() &&
                        is_numeric($response->getTransactionNumber())
                    ) {
                        $paymentStatus = $client->getPaymentStatus($paymentMethod, $response->getTransactionNumber());

                        /** 'committed', 'settled', 'verified', 'refunded', 'authorized', 'cancelled', 'subscribed' */
                        $_acceptableStatusCodes = array('committed', 'settled', 'verified', 'authorized');

                        if (
                            $paymentStatus->getCode() &&
                            in_array($paymentStatus->getCode(), $_acceptableStatusCodes) &&
                            $order->getBaseTotalDue()
                        ) {
                            $trans_id = preg_replace("/[^0-9]+/", "", $response->getTransactionNumber());
                            $_transactionId = $response->getTransactionNumber();

                            $this->_orderHelper->addNewOrderTransaction(
                                $order,
                                $_transactionId,
                                $trans_id,
                                \Magento\Sales\Model\Order::STATE_PROCESSING,
                                $response->getOrderGrossAmount() / 100,
                                $paymentMethod,
                                true
                            );

                            $this->_eventManager->dispatch('verifone_server_send_request_after', [
                                '_class' => get_class($this),
                                '_response' => $response,
                                '_orderId' => $order->getId()
                            ]);

                            if (!$order->getEmailSent()) {
                                $this->_orderHelper->sendEmail($order);
                            }

                            $this->_session->setPaymentMethod(null);
                            $this->_session->setPaymentMethodId(null);
                            $this->_session->setSavePaymentMethod(null);

                            $redirectUrl = 'checkout/onepage/success';
                            $resultRedirect->setPath($redirectUrl);
                            return $resultRedirect;
                        }
                    }

                } catch (Exception $e) {
                    $this->_logger->critical($e);
                    $redirectUrl = 'verifone_payment/payment/form';
                }
            }

        } else {
            $redirectUrl = 'checkout/cart';
        }

        $resultRedirect->setPath($redirectUrl, $redirectParams);
        return $resultRedirect;
    }
}