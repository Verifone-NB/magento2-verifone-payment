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

namespace Verifone\Payment\Model\Config\Source\Order;

class Status extends \Magento\Sales\Model\Config\Source\Order\Status
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        $options = parent::toOptionArray();
        if (isset($options[0]) && !$options[0]['value']) {
            unset($options[0]);
        }
        return $options;
    }
}