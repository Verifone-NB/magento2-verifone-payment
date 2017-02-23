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

namespace Verifone\Payment\Model\Client;

class DataValidator
{

    /**
     * @var array
     */
    protected $_requiredProductKeys = [
        'name',
        'unit_cost',
        'gross_amount',
        'net_amount',
        'unit_count',
        'vat_percentage'
    ];

    /**
     * @var array
     */
    protected $_requiredBasicKeys = [
        'currency_code',
        'total_incl_amount',
        'total_excl_amount',
        'total_vat',
        'order_id',
        'products'
    ];

    /**
     * @param array $data
     * @return bool
     */
    public function validateBasicData(array $data = [])
    {
        foreach ($this->getRequiredBasicKeys() as $key) {
            if (!isset($data[$key]) || $data[$key] === '') {
                return false;
            }
        }
        return true;
    }

    /**
     * @param array $data
     * @return bool
     */
    public function validateProductsData(array $data = [])
    {
        if (isset($data['products']) && !empty($data['products'])) {
            $requiredProductKeys = $this->getRequiredProductKeys();
            foreach ($data['products'] as $productData) {
                foreach ($requiredProductKeys as $key) {
                    if (!isset($productData[$key]) || $productData[$key] === '') {
                        return false;
                    }
                    if ($key === 'quantity' && !$this->validatePositiveFloat($productData[$key])) {
                        return false;
                    }
                }
            }
        }
        return true;
    }

    /**
     * @return array
     */
    protected function getRequiredBasicKeys()
    {
        return $this->_requiredBasicKeys;
    }

    /**
     * @return array
     */
    protected function getRequiredProductKeys()
    {
        return $this->_requiredProductKeys;
    }

    /**
     * @param mixed $data
     * @return bool
     */
    public function validateEmpty($data)
    {
        return !empty($data);
    }

    /**
     * @param mixed $value
     * @return bool
     */
    public function validatePositiveInt($value)
    {
        return is_integer($value) && $value > 0;
    }

    /**
     * @param mixed $value
     * @return bool
     */
    public function validatePositiveFloat($value)
    {
        return (is_integer($value) || is_float($value)) && $value > 0;
    }
}