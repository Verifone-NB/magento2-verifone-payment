<?php
/**
 *
 * NOTICE OF LICENSE
 *
 * This source file is released under commercial license by Lamia Oy.
 *
 * @copyright Copyright (c) 2017 Lamia Oy (https://lamia.fi)
 * @author    Szymon Nosal <simon@lamia.fi>
 */

namespace Verifone\Payment\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class BasketItemSending implements ArrayInterface
{

    const NO_SEND = 0;
    const SEND_FOR_ALL = 1;
    const SEND_FOR_INVOICE = 2;

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::NO_SEND, 'label' => __('Do not send basket items')],
            ['value' => self::SEND_FOR_ALL, 'label' => __('Send for all payment methods')],
            ['value' => self::SEND_FOR_INVOICE, 'label' => __('Send only for invoice payment methods')]
        ];
    }

}