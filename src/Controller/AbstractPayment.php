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

    /**
     * @var \Verifone\Payment\Helper\Order
     */
    protected $_order;

    /**
     * @var \Verifone\Payment\Model\ClientFactory
     */
    protected $_clientFactory;

    /**
     * AbstractPayment constructor.
     *
     * @param Context                        $context
     * @param \Verifone\Payment\Helper\Order $order
     */
    public function __construct(
        Context $context,
        \Verifone\Payment\Helper\Order $order,
        \Verifone\Payment\Model\ClientFactory $clientFactory
    ) {
        parent::__construct($context);

        $this->_order = $order;
        $this->_clientFactory = $clientFactory;

    }

    protected function _handleSuccess($delayedSuccess = false)
    {

        $_request = $this->_request;

        /** @var \Verifone\Payment\Model\Client\FormClient $client */
        $client = $this->_clientFactory->create('frontend');

        /** @var string $orderNumber */
        $orderNumber = $client->getOrderNumber($_request->getParams());

        if (empty($orderNumber)) {
            return $this->execute();
        }

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

        if ($order->getId()
            && $validate
            && $parsedResponse->getStatusCode() == CoreResponseConverter::STATUS_OK
            && empty($body->getCancelMessage())
        ) {

            $resultRedirect = $this->resultRedirectFactory->create();

            if ($order->getBaseTotalDue()) {
                $trans_id = preg_replace("/[^0-9]+/", "", $body->getTransactionNUmber());
                $_transactionId = $body->getTransactionNUmber();

                $this->_order->addNewOrderTransaction(
                    $order,
                    $_transactionId,
                    $trans_id,
                    \Magento\Sales\Model\Order::STATE_PROCESSING,
                    $body->getOrderGrossAmount() / 100,
                    $body->getPaymentMethodCode()
                );

                $this->_order->sendEmail($order);

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
        return $this->execute();
    }

    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $redirectUrl = 'checkout/cart';
        $resultRedirect->setPath($redirectUrl);

        $_request = $this->_request;

        /** @var \Verifone\Payment\Model\Client\FormClient $client */
        $client = $this->_clientFactory->create('frontend');

        /** @var string $orderNumber */
        $orderNumber = $client->getOrderNumber($_request->getParams());

        if (empty($orderNumber)) {
            return $resultRedirect;
        }

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

        $_transactionId = $body->getTransactionNUmber();
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
            $order->cancel();

            $history = __('Payment was canceled. Cancel reason: %1$s', $body->getCancelMessage());
            $order->addStatusHistoryComment($history, $order->getStatus());
        }

        $order->getResource()->save($order);

        return $resultRedirect;
    }

}