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
use Verifone\Payment\Helper\Path;

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
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\App\ProductMetadataInterface $productMetadata
     * @param \Magento\Framework\Module\Dir\Reader $reader
     * @param \Verifone\Payment\Helper\Payment $helper
     * @param \Magento\Framework\UrlInterface $urlBuilder
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        \Magento\Framework\Module\Dir\Reader $reader,
        \Verifone\Payment\Helper\Payment $helper,
        \Magento\Framework\UrlInterface $urlBuilder
    )
    {

        parent::__construct($scopeConfig, $storeManager, $productMetadata, $reader, $helper);

        $this->_urlBuilder = $urlBuilder;

    }

    public function prepareConfig()
    {
        parent::prepareConfig();

        $this->_config['skip-confirmation'] = (string)$this->_scopeConfig->getValue(Path::XML_PATH_SKIP_CONFIRMATION_PAGE);
        $this->_config['payment-url'] = $this->_prepareUrls(Path::XML_PATH_PAYMENT_URL);

        return true;
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

    public function getRedirectCardUrlsObject()
    {
        return new RedirectUrlsImpl(
            $this->_urlBuilder->getUrl('verifone_payment/customer_card_response/success'),
            $this->_urlBuilder->getUrl('verifone_payment/customer_card_response/rejected'),
            $this->_urlBuilder->getUrl('verifone_payment/customer_card_response/cancel'),
            $this->_urlBuilder->getUrl('verifone_payment/customer_card_response/expired'),
            $this->_urlBuilder->getUrl('verifone_payment/customer_card_response/error')
        );
    }
}