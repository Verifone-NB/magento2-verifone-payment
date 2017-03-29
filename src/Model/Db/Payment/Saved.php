<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is released under commercial license by Lamia Oy.
 *
 * @copyright Copyright (c) 2017 Lamia Oy (https://lamia.fi)
 * @author    Szymon Nosal <simon@lamia.fi>
 */

namespace Verifone\Payment\Model\Db\Payment;

use Magento\Framework\Model\AbstractModel;

class Saved extends AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{
    const CACHE_TAG = 'verifone_payment_saved';
    protected $_cacheTag = 'verifone_payment_saved';
    protected $_eventPrefix = 'verifone_payment_saved';

    protected function _construct()
    {
        $this->_init('Verifone\Payment\Model\ResourceModel\Db\Payment\Saved');
    }

    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }


}