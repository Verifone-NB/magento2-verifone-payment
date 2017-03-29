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

namespace Verifone\Payment\Helper;

class Order
{

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $_orderRepository;

    /**
     * @var \Magento\Checkout\Model\Session\SuccessValidator
     */
    protected $_checkoutSuccessValidator;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $_request;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $_searchCriteriaBuilder;

    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\OrderSender
     */
    protected $_orderSender;

    /**
     * @var \Verifone\Payment\Model\ResourceModel\Transaction
     */
    protected $_transactionResource;

    /**
     * @var \Verifone\Payment\Model\Order\Validator
     */
    protected $_orderValidator;

    public function __construct(
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Checkout\Model\Session\SuccessValidator $checkoutSuccessValidator,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Verifone\Payment\Model\Order\Validator $orderValidator,
        \Verifone\Payment\Model\ResourceModel\Transaction $transactionResource
    ) {
        $this->_orderRepository = $orderRepository;
        $this->_checkoutSuccessValidator = $checkoutSuccessValidator;
        $this->_checkoutSession = $checkoutSession;
        $this->_request = $request;
        $this->_searchCriteriaBuilder = $searchCriteriaBuilder;

        $this->_orderSender = $orderSender;
        $this->_orderValidator = $orderValidator;
        $this->_transactionResource = $transactionResource;
    }

    /**
     * Saves new order transaction.
     *
     * @param \Magento\Sales\Model\Order $order
     * @param string                     $transactionId
     * @param string                     $extOrderId
     * @param string                     $status
     */
    public function addNewOrderTransaction(
        \Magento\Sales\Model\Order $order,
        string $transactionId,
        string $extOrderId,
        string $status,
        float $amount,
        string $paymentMethod
    ) {
        $orderId = $order->getId();

        /**
         * @var \Magento\Sales\Model\Order\Payment $payment
         */
        $payment = $order->getPayment();

        $payment->setAdditionalInformation(
            [
                \Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS =>
                    [
                        'payment-method' => $paymentMethod
                    ]
            ]
        );
        $payment->setIsTransactionClosed(0);
        $payment->setTransactionId($transactionId);
        $payment->setTransactionAdditionalInfo(\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS,
            [
                'ext_order_id' => $extOrderId
            ]
        );

        /**
         * @var \Magento\Sales\Model\Order\Payment\Transaction $transaction
         */
        $transaction = $payment->addTransaction(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_ORDER);

        $payment->getResource()->save($payment);
        $transaction->getResource()->save($transaction);

        $order->setExtOrderId($extOrderId);
        $order->addStatusHistoryComment(__('Payment %1 captured', $transactionId), $status);
        $order->setState($status);

        $this->completePayment($order, $amount, $transactionId);

        $order->getResource()->save($order);
    }

    public function sendEmail(\Magento\Sales\Model\Order $order)
    {
        if (!$order->getEmailSent()) {
            $this->_orderSender->send($order);
        }

        return true;
    }


    /**
     * Registers payment, creates invoice and changes order statatus.
     *
     * @param \Magento\Sales\Model\Order $order
     * @param float                      $amount
     * @param string                     $transactionId
     *
     * @return void
     */
    public function completePayment(\Magento\Sales\Model\Order $order, float $amount, string $transactionId)
    {
        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $order->getPayment();

        $payment->setParentTransactionId($transactionId);
        $payment->setTransactionId($transactionId . ':C');
        $payment->registerCaptureNotification($amount);
        $payment->setIsTransactionClosed(1);

        $payment->getResource()->save($payment);

        /** @var \Magento\Framework\Model\AbstractModel $object */
        foreach ($order->getRelatedObjects() as $object) {
            $object->getResource()->save($object);
        }

        $order->getResource()->save($order);

    }

    /**
     * @param int $orderId
     *
     * @return \Magento\Sales\Model\Order|null
     */
    public function loadOrderById(int $orderId)/*: ?\Magento\Sales\Model\Order*/
    {
        /**
         * @var $order \Magento\Sales\Model\Order
         */
        $order = $this->_orderRepository->get($orderId);
        if ($order->getId()) {
            return $order;
        }

        return null;
    }

    /**
     * @param string $transactionId
     *
     * @return \Magento\Sales\Model\Order|null
     */
    public function loadOrderByTransaction(string $transactionId)/*: ?\Magento\Sales\Model\Order*/
    {
        $orderId = $this->_transactionResource->getOrderIdByTransactionId($transactionId);
        if ($orderId) {
            return $this->loadOrderById($orderId);
        }
        return null;
    }

    /**
     * @param string $incrementId
     *
     * @return \Magento\Sales\Model\Order|null
     */
    public function loadOrderByIncrementId(string $incrementId)
    {

        $searchCriteria = $this->_searchCriteriaBuilder->addFilter('increment_id', $incrementId, 'eq')->create();
        $orderList = $this->_orderRepository->getList($searchCriteria);

        if ($orderList->getTotalCount() == 0) {
            return null;
        }

        /** @var \Magento\Sales\Model\Order $item */
        foreach ($orderList->getItems() as $item) {
            return $item;
        }
    }

    /**
     * @return int|null
     */
    public function getOrderIdForPayment()/*: ?int*/
    {
        if ($this->_checkoutSuccessValidator->isValid()) {
            return $this->_checkoutSession->getLastOrderId();
        }
        $orderId = $this->_request->getParam('id');

        if ($orderId) {
            return $orderId;
        }

        return null;
    }

    /**
     * Checks payment can be start.
     *
     * @param \Magento\Sales\Model\Order $order
     *
     * @return bool
     */
    public function canStartPayment(\Magento\Sales\Model\Order $order): bool
    {
        return
            $this->_orderValidator->validateCustomer($order) &&
            $this->_orderValidator->validateNoTransactions($order) &&
            $this->_orderValidator->validatePaymentMethod($order) &&
            $this->_orderValidator->validateState($order);
    }
}