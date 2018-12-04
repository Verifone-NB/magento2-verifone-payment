<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is released under commercial license by Lamia Oy.
 *
 * @copyright Copyright (c) 2018 Lamia Oy (https://lamia.fi)
 */


namespace Verifone\Payment\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class HandlingMode implements ArrayInterface
{

    const SIMPLE_MODE = 0;
    const ADVANCED_MODE = 1;

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::SIMPLE_MODE, 'label' => __('Automatic (Simple)')],
            ['value' => self::ADVANCED_MODE, 'label' => __('Manual (Advanced)')]
        ];
    }

}