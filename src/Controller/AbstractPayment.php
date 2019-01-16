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

namespace Verifone\Payment\Controller;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Verifone\Core\Converter\Response\CoreResponseConverter;
use Verifone\Core\DependencyInjection\CoreResponse\PaymentResponseImpl;
use Verifone\Core\DependencyInjection\Transporter\CoreResponse;
use Verifone\Core\Exception\ResponseCheckFailedException;
use Verifone\Payment\Model\Order\Exception;

abstract class AbstractPayment extends Action
{

    const RETRY_DELAY_IN_SECONDS = 2;
    const RETRY_MAX_ATTEMPTS = 5;

    /**
     * @var \Verifone\Payment\Helper\Order
     */
    protected $_order;

    /**
     * @var \Verifone\Payment\Model\ClientFactory
     */
    protected $_clientFactory;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_session;

    /**
     * @var \Verifone\Payment\Model\ResourceModel\Db\Order\Process\Status
     */
    protected $_orderLocker;

    /**
     * AbstractPayment constructor.
     * @param Context $context
     * @param \Verifone\Payment\Helper\Order $order
     * @param \Verifone\Payment\Model\ClientFactory $clientFactory
     */
    public function __construct(
        Context $context,
        \Verifone\Payment\Helper\Order $order,
        \Verifone\Payment\Model\ClientFactory $clientFactory,
        \Magento\Checkout\Model\Session $session,
        \Verifone\Payment\Model\ResourceModel\Db\Order\Process\Status $status
    ) {
        parent::__construct($context);

        $this->_order = $order;
        $this->_clientFactory = $clientFactory;
        $this->_session = $session;
        $this->_orderLocker = $status;
    }

    protected function _handleSuccess($delayedSuccess = false)
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $redirectUrl = 'checkout/cart';
        $resultRedirect->setPath($redirectUrl);

        $_request = $this->getRequest();
        $_signedFormData = $_request->getParams();

        /** @var \Verifone\Payment\Model\Client\FormClient $client */
        $client = $this->_clientFactory->create('frontend');

        /** @var string $orderNumber */
        $orderNumber = $client->getOrderNumber($_request->getParams());

        if (empty($orderNumber)) {
             return self::execute();
        }

        /**
         * @var \Magento\Sales\Model\Order $order
         */
        $order = $this->_order->loadOrderByIncrementId($orderNumber);

        if($delayedSuccess === false && $order->getState() === \Magento\Sales\Model\Order::STATE_PROCESSING ) {
            $redirectUrl = 'checkout/onepage/success';
            $resultRedirect->setPath($redirectUrl);
            return $resultRedirect;
        }

        $attempts = 0;
        while(!$this->_orderLocker->lockOrder($orderNumber) && $attempts < self::RETRY_MAX_ATTEMPTS) {
            sleep(self::RETRY_DELAY_IN_SECONDS);
            ++$attempts;
        }

        // Check if order status changed during a while
        if($attempts > 0 && $attempts < self::RETRY_MAX_ATTEMPTS ) {
            $orderTmp = $this->_order->loadOrderByIncrementId($orderNumber);

            if($delayedSuccess === false && $orderTmp->getState() === \Magento\Sales\Model\Order::STATE_PROCESSING ) {
                $redirectUrl = 'checkout/onepage/success';
                $resultRedirect->setPath($redirectUrl);
                return $resultRedirect;
            }

        }

        try {
            /** @var CoreResponse $validate */
            $parsedResponse = $client->validateAndParseResponse($_request->getParams(), $order);

            /** @var PaymentResponseImpl $body */
            $body = $parsedResponse->getBody();

            $validate = true;
        } catch (ResponseCheckFailedException $e) {
            $validate = false;
            $parsedResponse = null;
            $body = null;
        } catch (Exception $e) {
            $validate = false;
            $parsedResponse = null;
            $body = null;
        }

        if ($order->getId()
            && $validate
            && $parsedResponse->getStatusCode() == CoreResponseConverter::STATUS_OK
            && empty($body->getCancelMessage())
        ) {

            $resultRedirect = $this->resultRedirectFactory->create();

            if ($order->getBaseTotalDue()) {

                $this->_eventManager->dispatch('verifone_paymentinterface_send_request_after', [
                    '_class' => get_class($this),
                    '_response' => $_signedFormData,
                    '_success' => true
                ]);

                $trans_id = preg_replace("/[^0-9]+/", "", $body->getTransactionNumber());
                $_transactionId = $body->getTransactionNumber();

                $this->_order->addNewOrderTransaction(
                    $order,
                    $_transactionId,
                    $trans_id,
                    \Magento\Sales\Model\Order::STATE_PROCESSING,
                    $body->getOrderGrossAmount() / 100,
                    $body->getPaymentMethodCode()
                );

                $session = $this->_session;
                $session->getQuote()->setIsActive(false)->getResource()->save($session->getQuote());

                if(!$order->getEmailSent()) {
                    $this->_order->sendEmail($order);
                }

                $this->_session->setPaymentMethod(null);
                $this->_session->setPaymentMethodId(null);
                $this->_session->setSavePaymentMethod(null);

                $this->_orderLocker->unlockOrder($orderNumber);

                if ($delayedSuccess) {
                    // no session, i.e. late POST from the payment system. We must signal 200 OK.
                    header("HTTP/1.1 200 OK");
                    die('<html><head><meta http-equiv="refresh" content="0;url=' . $this->_url->getUrl('checkout/cart') . '"></head></html>');
                } else {
                    $redirectUrl = 'checkout/onepage/success';
                    $resultRedirect->setPath($redirectUrl);
                    return $resultRedirect;
                }
            }
        }

        // invalid:
        return self::execute();
    }

    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $redirectUrl = 'checkout/cart';
        $resultRedirect->setPath($redirectUrl);

        $_request = $this->getRequest();
        $_signedFormData = $_request->getParams();

        /** @var \Verifone\Payment\Model\Client\FormClient $client */
        $client = $this->_clientFactory->create('frontend');

        /** @var string $orderNumber */
        $orderNumber = $client->getOrderNumber($_request->getParams());

        if (empty($orderNumber)) {
            return $resultRedirect;
        }

        $this->_eventManager->dispatch('verifone_paymentinterface_send_request_after', [
            '_class' => get_class($this),
            '_response' => $_signedFormData,
            '_success' => false
        ]);

        /**
         * @var \Magento\Sales\Model\Order $order
         */
        $order = $this->_order->loadOrderByIncrementId($orderNumber);

        try {
            /** @var CoreResponse $validate */
            $parsedResponse = $client->validateAndParseResponse($_request->getParams(), $order);

            /** @var PaymentResponseImpl $body */
            $body = $parsedResponse->getBody();

            $validate = true;
        } catch (ResponseCheckFailedException $e) {
            $validate = false;
            $parsedResponse = null;
            $body = null;
        } catch (Exception $e) {
            $validate = false;
            $parsedResponse = null;
            $body = null;
        }

        if (!$validate) {
            return $resultRedirect;
        }

        $_transactionId = $body->getTransactionNumber();
        if ($_transactionId) {

            $payment = $order->getPayment();

            if (!$payment) {
                return $resultRedirect;
            }

            $payment
                ->setTransactionId($_transactionId)
                ->setIsTransactionClosed(1);

            $payment->addTransaction(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_PAYMENT);
            $payment->getResource()->save($payment);
        }

        if (!in_array($order->getState(), array(\Magento\Sales\Model\Order::STATE_CANCELED))) {

            $session = $this->_session;
            // restore the quote
            $session->restoreQuote();

            $history = __('Payment was canceled. Cancel reason: %1', $body->getCancelMessage());
            $order->registerCancellation($history, $order->getStatus());
            $order->getResource()->save($order);
        }

        return $resultRedirect;
    }

}