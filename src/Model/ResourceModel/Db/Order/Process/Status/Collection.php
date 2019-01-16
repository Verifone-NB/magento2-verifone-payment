<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is released under commercial license by Lamia Oy.
 *
 * @copyright Copyright (c) 2019 Lamia Oy (https://lamia.fi)
 */


namespace Verifone\Payment\Model\ResourceModel\Db\Order\Process\Status;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * Define model & resource model
     */
    protected function _construct()
    {
        $this->_init(
            'Verifone\Payment\Model\Db\Order\Process\Status',
            'Verifone\Payment\Model\ResourceModel\Db\Order\Process\Status'
        );
    }

}