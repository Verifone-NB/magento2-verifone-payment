<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is released under commercial license by Lamia Oy.
 *
 * @copyright Copyright (c) 2019 Lamia Oy (https://lamia.fi)
 */


namespace Verifone\Payment\Model\Db\Order\Process;

use Magento\Framework\Model\AbstractModel;
use Verifone\Payment\Model\Order\Exception;

class Status extends AbstractModel
{
    const CACHE_TAG = 'verifone_order_process_status';
    protected $_cacheTag = 'verifone_order_process_status';
    protected $_eventPrefix = 'verifone_order_process_status';

    protected function _construct()
    {
        $this->_init('Verifone\Payment\Model\ResourceModel\Db\Payment\Saved');
    }

}