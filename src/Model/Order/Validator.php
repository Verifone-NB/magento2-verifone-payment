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

namespace Verifone\Payment\Model\Order;

class Validator
{
    /**
     * @var \Verifone\Payment\Model\ResourceModel\Transaction
     */
    protected $_transactionResource;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    public function __construct(
        \Verifone\Payment\Model\ResourceModel\Transaction $transactionResource,
        \Magento\Customer\Model\Session $customerSession
    ) {
        $this->_transactionResource = $transactionResource;
        $this->_customerSession = $customerSession;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     *
     * @return bool
     */
    public function validateNoTransactions(\Magento\Sales\Model\Order $order)
    {
        return $this->_transactionResource->getLastVerifoneOrderIdByOrderId($order->getId()) === null;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     *
     * @return bool
     */
    public function validatePaymentMethod(\Magento\Sales\Model\Order $order)
    {
        return $order->getPayment()->getMethod() === \Verifone\Payment\Model\Payment::CODE;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     *
     * @return bool
     */
    public function validateState(\Magento\Sales\Model\Order $order)
    {
        return !in_array($order->getState(), [
            \Magento\Sales\Model\Order::STATE_CANCELED,
            \Magento\Sales\Model\Order::STATE_CLOSED,
            \Magento\Sales\Model\Order::STATE_COMPLETE
        ]);
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     *
     * @return bool
     */
    public function validateCustomer(\Magento\Sales\Model\Order $order)
    {
        return $order->getCustomerId() === $this->_customerSession->getCustomerId();
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     *
     * @return bool
     */
    public function validateNotPaid(\Magento\Sales\Model\Order $order)
    {
        return !$order->getTotalPaid();
    }
}