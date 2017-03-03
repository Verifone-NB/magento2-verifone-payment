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

namespace Verifone\Payment\Model\Db\Payment;

use Magento\Framework\Model\AbstractModel;
use Verifone\Payment\Model\Order\Exception;

/**
 * @method string getCode()
 * @method string getType()
 * @method int getActive()
 * @method setActive(integer $active)
 */
class Method extends AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{
    const CACHE_TAG = 'verifone_payment_method';

    protected $_cacheTag = 'verifone_payment_method';

    protected $_eventPrefix = 'verifone_payment_method';

    const TYPE_BANK = 'BANK';
    const TYPE_CARD = 'CARD';
    const TYPE_INVOICE = 'INVOICE';

    protected function _construct()
    {
        $this->_init('Verifone\Payment\Model\ResourceModel\Db\Payment\Method');
    }

    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    public function isActive()
    {
        return (bool)$this->getActive();
    }

    public function isInvoice()
    {
        return $this->getType() == self::TYPE_INVOICE;
    }

    /**
     * @param $code
     *
     * @return Method|\Magento\Framework\DataObject
     */
    public function loadByCode($code)
    {
        return $this->getCollection()->addFieldToFilter('code', array('eq' => $code))->getFirstItem();
    }

    /**
     * @return \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
     */
    public function getActiveMethods()
    {
        return $this->getCollection()->addFieldToFilter('active', array('eq' => 1));
    }

    public function getName()
    {
        return __($this->getData('name'));
    }

    /**
     * @param $methods
     *
     * @return string|\Verifone\Payment\Model\ResourceModel\Db\Payment\Method\Collection
     */
    public function refreshMethods($methods)
    {
        $collection = $this->getCollection();

        /** @var Method $item */
        foreach ($collection as $item) {
            if(in_array($item->getCode(), $methods)) {
                $item->setActive(1);
            } else {
                $item->setActive(0);
            }

            try{
                $item->getResource()->save($item);
            } catch (Exception $e) {
                return $e->getMessage();
            }
        }

        return $this->getActiveMethods();
    }
}