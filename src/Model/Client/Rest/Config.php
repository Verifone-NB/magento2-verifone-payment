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

namespace Verifone\Payment\Model\Client\Rest;

use Verifone\Payment\Helper\Path;

class Config extends \Verifone\Payment\Model\Client\Config
{

    /**
     * Config constructor.
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\App\ProductMetadataInterface $productMetadata
     * @param \Magento\Framework\Module\Dir\Reader $reader,
     * @param \Verifone\Payment\Helper\Payment $helper
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        \Magento\Framework\Module\Dir\Reader $reader,
        \Verifone\Payment\Helper\Payment $helper,
        \Verifone\Payment\Helper\Keys $keysHelper
    )
    {

        parent::__construct($scopeConfig, $storeManager, $productMetadata, $reader, $helper, $keysHelper);

    }

    public function prepareConfig()
    {
        parent::prepareConfig();

        $this->_config['server-url'] = $this->_prepareUrls(Path::XML_PATH_SERVER_URL);
    }

}