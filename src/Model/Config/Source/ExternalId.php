<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is released under commercial license by Lamia Oy.
 *
 * @copyright Copyright (c) 2017 Lamia Oy (https://lamia.fi)
 * @author    Szymon Nosal <simon@lamia.fi>
 */

namespace Verifone\Payment\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class ExternalId implements ArrayInterface
{

    protected $_objectManager;

    public function __construct(
        //\Magento\Customer\Model\Customer $customerFactory,
        \Magento\Framework\ObjectManagerInterface $interface
    )
    {
        // $this->customerFactory = $customerFactory;
        $this->_objectManager = $interface;
    }

    /**
     * Return array of options as value-label pairs
     *
     * @return array Format: array(array('value' => '<value>', 'label' => '<label>'), ...)
     */
    public function toOptionArray($isMultiselect = false)
    {
        $customerAttributes = $this->_objectManager->get('Magento\Customer\Model\Customer')->getAttributes();

        $attributesArrays = [
            [
                'label' => '',
                'value' => ''
            ]
        ];

        foreach ($customerAttributes as $code => $attribute) {
            $attributesArrays[] = [
                'label' => $attribute->getStoreLabel() ? $attribute->getStoreLabel() : $code,
                'value' => $code
            ];
        }

        usort($attributesArrays, array("self", "sortAttribute"));

        return $attributesArrays;
    }

    public function sortAttribute($attributeA, $attributeB)
    {
        return strcasecmp($attributeA['label'], $attributeB['label']);
    }
}