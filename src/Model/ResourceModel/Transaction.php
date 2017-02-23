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

namespace Verifone\Payment\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Transaction extends AbstractDb
{

    /**
     * @var \Magento\Framework\Stdlib\DateTime
     */
    protected $_date;

    /**
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param \Magento\Framework\Stdlib\DateTime                $date
     * @param string|null                                       $connectionName
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\Framework\Stdlib\DateTime $date,
        $connectionName = null
    ) {
        parent::__construct(
            $context,
            $connectionName
        );
        $this->_date = $date;
    }

    /**
     * @param int $orderId
     *
     * @return string|null
     */
    public function getLastVerifoneOrderIdByOrderId(int $orderId)/*: ?string*/
    {
        $adapter = $this->getConnection();
        $select = $adapter->select()
            ->from(
                ['main_table' => $this->_resources->getTableName('sales_payment_transaction')],
                ['txn_id']
            )->where('order_id = ?', $orderId)
            ->where('txn_type = ?', 'order')
            ->order('transaction_id ' . \Zend_Db_Select::SQL_DESC)
            ->limit(1);
        $row = $adapter->fetchRow($select);
        if ($row) {
            return $row['txn_id'];
        }
        return null;
    }

    /**
     * @param string $transactionId
     *
     * @return int|null
     */
    public function getOrderIdByTransactionId(string $transactionId)/*: ?int*/
    {
        return $this->getOneFieldByAnother('order_id', 'txn_id', $transactionId);
    }

    /**
     * @param string $transactionId
     *
     * @return int|null
     */
    public function getIdByTransactionId(string $transactionId)/*: ?int*/
    {
        return $this->getOneFieldByAnother('transaction_id', 'txn_id', $transactionId);
    }

    /**
     * @param string $getFieldName
     * @param string $byFieldName
     * @param mixed  $value
     *
     * @return int|null
     */
    protected function getOneFieldByAnother($getFieldName, $byFieldName, $value)/*: ?int*/
    {
        $adapter = $this->getConnection();
        $select = $adapter->select()
            ->from(
                ['main_table' => $this->_resources->getTableName('sales_payment_transaction')],
                [$getFieldName]
            )->where($byFieldName . ' = ?', $value)
            ->limit(1);
        $row = $adapter->fetchRow($select);
        if ($row) {
            return $row[$getFieldName];
        }
        return null;
    }


    /**
     * Resource initialization
     *
     * @return void
     */
    protected function _construct()
    {
        // Required by parent class
    }
}