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

namespace Verifone\Payment\Model\Client\Form;

use Verifone\Core\DependencyInjection\Configuration\Frontend\RedirectUrlsImpl;

class Config extends \Verifone\Payment\Model\Client\Config
{

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $_urlBuilder;

    /**
     * Config constructor.
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Store\Model\StoreManagerInterface         $storeManager
     * @param \Magento\Framework\App\ProductMetadataInterface    $productMetadata
     * @param \Magento\Framework\App\Filesystem\DirectoryList    $directoryList
     * @param \Verifone\Payment\Helper\Payment                   $helper
     * @param \Magento\Framework\UrlInterface                    $urlBuilder
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Verifone\Payment\Helper\Payment $helper,
        \Magento\Framework\UrlInterface $urlBuilder
    ) {

        parent::__construct($scopeConfig, $storeManager, $productMetadata, $directoryList, $helper);

        $this->_urlBuilder = $urlBuilder;

        return $this;

    }

    public function getRedirectUrlsObject()
    {
        return new RedirectUrlsImpl(
            $this->_urlBuilder->getUrl('verifone_payment/payment/success'),
            $this->_urlBuilder->getUrl('verifone_payment/payment/rejected'),
            $this->_urlBuilder->getUrl('verifone_payment/payment/cancel'),
            $this->_urlBuilder->getUrl('verifone_payment/payment/expired'),
            $this->_urlBuilder->getUrl('verifone_payment/payment/error')
        );
    }
}