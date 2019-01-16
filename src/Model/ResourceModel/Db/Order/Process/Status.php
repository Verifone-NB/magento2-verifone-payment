<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is released under commercial license by Lamia Oy.
 *
 * @copyright Copyright (c) 2019 Lamia Oy (https://lamia.fi)
 */


namespace Verifone\Payment\Model\ResourceModel\Db\Order\Process;


class Status extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Define main table
     */
    protected function _construct()
    {
        $this->_init('verifone_order_process_status', 'order_id');
    }

    public function lockOrder($orderId)
    {
        $connection = $this->getConnection();
        $result = false;

        try {
            $this->beginTransaction();

            $findQuery = $connection->select()
                ->from($this->getMainTable())
                ->where('order_id = ?', $orderId);

            if ($connection->fetchOne($findQuery)) {
                $result = (bool)$connection->update(
                    $this->getMainTable(),
                    ['under_process' => 1],
                    ['under_process = ?' => 0, 'order_id = ?' => $orderId]
                );
            } else {
                $result = (bool)$connection->insert($this->getMainTable(), ['order_id' => $orderId, 'under_process' => 1]);
            }


            $this->commit();
        } catch (\Exception $e) {
            $this->rollBack();
        }

        return $result;
    }

    public function unlockOrder($orderId)
    {
        $result = false;

        try{
            $this->beginTransaction();

            $result=(bool)$this->getConnection()
                ->update(
                    $this->getMainTable(),
                    ['under_process' => 0],
                    ['under_process = ?' => 1, 'order_id = ?' => $orderId]
                );

            $this->commit();
        } catch (\Exception $e) {
            $this->rollBack();
        }

        return $result;
    }
}