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

namespace Verifone\Payment\Model\Config\Source;

class LiveMode
{
    const LIVE = 1;
    const TEST = 0;

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::LIVE, 'label' => __('Production')],
            ['value' => self::TEST, 'label' => __('Test')],
        ];
    }
}