<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is released under commercial license by Lamia Oy.
 *
 * @copyright Copyright (c) 2017 Lamia Oy (https://lamia.fi)
 * @author    Szymon Nosal <simon@lamia.fi>
 */


namespace Verifone\Payment\Cron;


class OrderStatus
{

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    protected $_orderCollectionFactory;

    /**
     * @var \Verifone\Payment\Helper\Order
     */
    protected $_order;

    public function __construct(
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Verifone\Payment\Helper\Order $order
    )
    {
        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->_order = $order;

    }

    public function execute()
    {

        $orders = $this->_orderCollectionFactory->create()
            ->addFieldToSelect('*')
            ->addFieldToFilter('state', 'pending_payment')
            ->addFieldToFilter('created_at', array(
                'gt' => new \Zend_Db_Expr('NOW() - INTERVAL 3 HOUR')
            ));

        /** @var \Magento\Sales\Model\Order $order */
        foreach($orders as $order) {
            if($order->getPayment()->getMethod() === \Verifone\Payment\Model\Payment::CODE) {
                $this->_order->getTransactions($order->getIncrementId(), true, true);
            }
        }

        return $this;
    }
}