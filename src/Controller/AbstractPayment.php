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

abstract class AbstractPayment extends Action
{

    /**
     * @var \Verifone\Payment\Helper\Order
     */
    protected $_order;

    /**
     * AbstractPayment constructor.
     *
     * @param Context                         $context
     * @param \Verifone\Payment\Helper\Order  $order
     */
    public function __construct(
        Context $context,
        \Verifone\Payment\Helper\Order $order
    ) {
        parent::__construct($context);

        $this->_order = $order;
    }

    protected function _handleSuccess($delayedSuccess = false)
    {

        $_request = $this->_request;
        $signedFormData = $_request->getParams();

        /**
         * @var \Magento\Sales\Model\Order $order
         */
        $order = $this->_order->loadOrderByIncrementId($_request->getParam('s-f-1-36_order-number'));

        if ($order->getId()
            && $_request->getParam('l-f-1-20_transaction-number')
            && abs($_request->getParam('l-f-1-20_order-gross-amount') - (round($order->getGrandTotal(), 2) * 100)) < 1
            && !$_request->getParam('s-t-1-30_cancel-reason')
        ) {

            $resultRedirect = $this->resultRedirectFactory->create();

            if ($order->getBaseTotalDue()) {
                $trans_id = preg_replace("/[^0-9]+/", "", $_request->getParam('l-f-1-20_transaction-number'));
                $_transactionId = $_request->getParam('l-f-1-20_transaction-number');

                $this->_order->addNewOrderTransaction(
                    $order,
                    $_transactionId,
                    $trans_id,
                    \Magento\Sales\Model\Order::STATE_PROCESSING,
                    $_request->getParam('l-f-1-20_order-gross-amount') / 100,
                    $_request->getParam('s-f-1-30_payment-method-code')
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
        $redirectUrl = 'checkout/onepage';
        $resultRedirect->setPath($redirectUrl);

        $_request = $this->_request;
        $signedFormData = $_request->getParams();

        /**
         * @var \Magento\Sales\Model\Order $order
         */
        $order = $this->_order->loadOrderByIncrementId($_request->getParam('s-f-1-36_order-number'));

        $_signatureValid = true;
        /**
         * @todo: add validation
         */
        if (!$_signatureValid) {
            return $resultRedirect;
        }

        $_transactionId = $_request->getParam('l-f-1-20_transaction-number');
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

            $history = sprintf(__('Payment was canceled. Cancel reason: ') . $_request->getParam('s-t-1-30_cancel-reason'));
            $order->addStatusHistoryComment($history, $order->getStatus());
        }

        $order->getResource()->save($order);

        return $resultRedirect;
    }
}