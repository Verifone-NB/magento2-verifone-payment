<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is released under commercial license by Lamia Oy.
 *
 * @copyright Copyright (c) 2017 Lamia Oy (https://lamia.fi)
 * @author    Szymon Nosal <simon@lamia.fi>
 */


namespace Verifone\Payment\Block\System\Config;

class DelayedSuccessUrl extends \Magento\Config\Block\System\Config\Form\Field
{

    /**
     * @var \Verifone\Payment\Helper\Urls
     */
    protected $_urls;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Verifone\Payment\Helper\Urls $urls,
        array $data = [])
    {
        parent::__construct($context, $data);

        $this->_urls = $urls;
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
        return $this->_urls->getSuccessDelayedUrl();
    }
}