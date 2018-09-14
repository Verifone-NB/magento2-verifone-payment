<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is released under commercial license by Lamia Oy.
 *
 * @copyright Copyright (c) 2018 Lamia Oy (https://lamia.fi)
 */


namespace Verifone\Payment\Block\System\Config;


use Verifone\Payment\Helper\Path;

class PublicShopKey extends \Magento\Config\Block\System\Config\Form\Field
{

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        array $data = [])
    {
        parent::__construct($context, $data);
    }

    /**
     * Get the button and scripts contents
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     *
     * @return string
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {

        $merchantAgreementCode = $this->_scopeConfig->getValue(Path::XML_PATH_MERCHANT_CODE);
        $privateKeyFile = $this->_scopeConfig->getValue(Path::XML_PATH_KEY_SHOP);

        $dir = \dirname($privateKeyFile);
        $prefix = $dir . DIRECTORY_SEPARATOR . $merchantAgreementCode;

        $path = $prefix . '-public.pem';

        if(file_exists($path)) {
            return '<pre>' . \file_get_contents($path) . '</pre>';
        }

        return 'Error! Public key does not exist.';
    }
}