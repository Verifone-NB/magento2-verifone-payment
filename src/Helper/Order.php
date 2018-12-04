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

use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\ResourceModel\Order\Payment\Transaction\CollectionFactory as TransactionCollectionFactory;
use Verifone\Core\DependencyInjection\CoreResponse\PaymentStatusImpl;
use Verifone\Core\DependencyInjection\Service\TransactionImpl;
use Verifone\Payment\Model\Client\RestClient;

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

    /**
     * @var TransactionCollectionFactory
     */
    protected $_salesTransactionCollectionFactory;

    /**
     * @var \Verifone\Payment\Model\ClientFactory
     */
    protected $_clientFactory;

    /**
     * @var \Verifone\Payment\Helper\Payment
     */
    protected $_paymentHelper;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $_messageManager;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $_dateTime;

    public function __construct(
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Checkout\Model\Session\SuccessValidator $checkoutSuccessValidator,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Verifone\Payment\Model\Order\Validator $orderValidator,
        \Verifone\Payment\Model\ResourceModel\Transaction $transactionResource,
        TransactionCollectionFactory $salesTransactionCollectionFactory,
        \Verifone\Payment\Model\ClientFactory $clientFactory,
        \Verifone\Payment\Helper\Payment $paymentHelper,
        \Magento\Framework\Message\ManagerInterface $manager,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $dateTime
    )
    {
        $this->_orderRepository = $orderRepository;
        $this->_checkoutSuccessValidator = $checkoutSuccessValidator;
        $this->_checkoutSession = $checkoutSession;
        $this->_request = $request;
        $this->_searchCriteriaBuilder = $searchCriteriaBuilder;

        $this->_orderSender = $orderSender;
        $this->_orderValidator = $orderValidator;
        $this->_transactionResource = $transactionResource;
        $this->_salesTransactionCollectionFactory = $salesTransactionCollectionFactory;
        $this->_clientFactory = $clientFactory;
        $this->_paymentHelper = $paymentHelper;
        $this->_messageManager = $manager;
        $this->_dateTime = $dateTime;
    }

    /**
     * Saves new order transaction.
     *
     * @param \Magento\Sales\Model\Order $order
     * @param string $transactionId
     * @param string $extOrderId
     * @param string $status
     * @param float $amount
     * @param string $paymentMethod
     * @param bool $savedCard
     */
    public function addNewOrderTransaction(
        \Magento\Sales\Model\Order $order,
        string $transactionId,
        string $extOrderId,
        string $status,
        float $amount,
        string $paymentMethod,
        bool $savedCard = false,
        string $paymentStatusCode = null
    )
    {
        $orderId = $order->getId();
        $date = new \DateTime(date('Y-m-d H:i'));

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
                'ext_order_id' => $extOrderId,
                'date_of_check' => $date,
                'order_gross_amount' => $amount,
                'payment_status_code' => $paymentStatusCode
            ]
        );

        if($order->getData('payment_method_code') !== $paymentMethod) {
            $order->setData('payment_method_code', $paymentMethod);
        }

        /**
         * @var \Magento\Sales\Model\Order\Payment\Transaction $transaction
         */
        $transaction = $payment->addTransaction(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_ORDER);

        $payment->getResource()->save($payment);
        $transaction->getResource()->save($transaction);

        $order->setExtOrderId($extOrderId);
        if ($savedCard) {
            $order->addStatusHistoryComment(__('Payment %1 captured using saved CC', $transactionId), $status);
        } else {
            $order->addStatusHistoryComment(__('Payment %1 captured', $transactionId), $status);
        }
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
     * @param float $amount
     * @param string $transactionId
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

    public function getTransactions($orderIncrementId, $update = false, $cron = false)
    {
        $transactions = $transaction = [];

        $order = $this->loadOrderByIncrementId($orderIncrementId);

        $statuses = [
            $order::STATE_NEW,
            $order::STATE_PENDING_PAYMENT,
            'pending',
            'pending_verifone',
            $order::STATE_CANCELED,
        ];

        if (!in_array($order->getStatus(), $statuses)) {
            return false;
        }

        if (!$this->_transactionsCanBeCheck($order) && $cron) {
            return false;
        }

        /** @var RestClient $client */
        $client = $this->_clientFactory->create('backend');

        $response = $client->getTransactionsFromGate($orderIncrementId);

        if (is_null($response)) {
            return false;
        }

        $totalPaid = 0;

        /** @var TransactionImpl $item */
        foreach ($response as $item) {
            $transactionCode = $item->getMethodCode();
            $transactionNumber = $item->getNumber();

            /** @var PaymentStatusImpl $transaction */
            $transaction = $client->getPaymentStatus($transactionCode, $transactionNumber);

            if (!is_null($transaction)) {
                $transactions[] = $transaction;

                $totalPaid += $transaction->getOrderAmount();

                if ($update) {
                    $this->_updateTransaction($order, $transaction);
                }
            }

            if($totalPaid >= $order->getTotalDue()) {
                break;
            }

        }

        if (!empty($transaction) && $update) {
            $state = $this->_paymentHelper->getOrderStatusFromMap($transaction->getCode());

            if($state == $order->getState()){
                return true;
            }

            if ($status = $state) {
                $comment = __('Order status is now [%1] because payment for this order was found.', $state);

                if(in_array($state, array(
                    \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT,
                    'pending_verifone'
                ))){
                    $comment = __('Order status is now [%1] because payment for this order was NOT found.', $state);
                } elseif($state == \Magento\Sales\Model\Order::STATE_CANCELED) {
                    $comment = __('Order status is now [cancel] because payment for this order was cancelled');
                }

                if (!$cron) {
                    $this->_messageManager->addNoticeMessage($comment);
                }

            }
        }

        return true;
    }

    /**
     *
     *
     * @param \Magento\Sales\Model\Order $order
     * @return bool
     */
    protected function _transactionsCanBeCheck(\Magento\Sales\Model\Order $order)
    {

        $orderDate = new \DateTime($this->_dateTime->date(new \DateTime($order->getCreatedAt()))->format('Y-m-d H:i:s'));
        $date = new \DateTime($this->_dateTime->date()->format('Y-m-d H:i:s'));

        $diff = $date->diff($orderDate);

        if($diff->days > 0 || $diff->h > 1 || $diff->i < 15 || $diff->i > 60) {
            return false;
        }

        return true;
    }

    protected function _updateTransaction(\Magento\Sales\Model\Order $order, PaymentStatusImpl $transaction)
    {
        $trans_id = preg_replace("/[^0-9]+/", "", $transaction->getTransactionNumber());
        $date = new \DateTime(date('Y-m-d H:i'));

        // load transaction
        $collection = $this->_salesTransactionCollectionFactory->create();

        /** @var Transaction $paymentTransaction */
        $paymentTransaction = $collection->addFieldToFilter('transaction_id', array('eq' => $transaction->getTransactionNumber()))->getFirstItem();

        // if exists then update
        if($paymentTransaction && $paymentTransaction->getId()) {

            $paymentTransaction
                ->setTxnId($transaction->getTransactionNumber())
                ->setTxnType($this->_paymentHelper->getTransactionTypeFromMap($transaction->getCode()))
                ->setAdditionalInformation(\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS,
                    [
                        'ext_order_id' => $trans_id,
                        'date_of_check' => $date,
                        'order_gross_amount' => $transaction->getOrderAmount(),
                        'payment_status_code' => $this->_paymentHelper->getTransactionTypeFromMap($transaction->getCode())
                    ]);

            $paymentTransaction->getResource()->save($paymentTransaction);
            return true;
        }

        $state = $this->_paymentHelper->getOrderStatusFromMap($transaction->getCode());

        if($state == \Magento\Sales\Model\Order::STATE_CANCELED) {
            $this->_cancelTransacion(
                $order,
                $transaction->getTransactionNumber()
            );
        } elseif ($state == \Magento\Sales\Model\Order::STATE_PROCESSING) {
            // else create new
            $this->addNewOrderTransaction(
                $order,
                $transaction->getTransactionNumber(),
                $trans_id,
                \Magento\Sales\Model\Order::STATE_PROCESSING,
                $transaction->getOrderAmount() / 100,
                $transaction->getPaymentMethodCode(),
                false,
                $this->_paymentHelper->getTransactionTypeFromMap($transaction->getCode())
            );
        } else {

        }

        return true;
    }

    protected function _cancelTransacion(
        \Magento\Sales\Model\Order $order,
        $transactionId
    ) {
        if ($transactionId) {

            $payment = $order->getPayment();

            if (!$payment) {
                return false;
            }

            $payment
                ->setTransactionId($transactionId)
                ->setIsTransactionClosed(1);

            $payment->addTransaction(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_PAYMENT);
            $payment->getResource()->save($payment);
        }

        if (!in_array($order->getState(), array(\Magento\Sales\Model\Order::STATE_CANCELED))) {

            $order->cancel();

            $history = __('Order status is now [cancel] because payment for this order was cancelled');
            $order->addStatusHistoryComment($history, $order->getStatus());
        }

        return true;
    }
}