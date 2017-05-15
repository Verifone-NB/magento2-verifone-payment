<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is released under commercial license by Lamia Oy.
 *
 * @copyright Copyright (c) 2017 Lamia Oy (https://lamia.fi)
 * @author    Szymon Nosal <simon@lamia.fi>
 */


namespace Verifone\Payment\Helper;

class Urls
{

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $_urlFrontBuilder;

    public function __construct(
        \Magento\Framework\UrlInterface $urlFrontBuilder
    )
    {
        $this->_urlFrontBuilder = $urlFrontBuilder;
    }

    public function getSuccessDelayedUrl()
    {
        return $this->_urlFrontBuilder->getUrl('verifone_payment/payment/successDelayed', ['_nosid' => true]);
    }

}