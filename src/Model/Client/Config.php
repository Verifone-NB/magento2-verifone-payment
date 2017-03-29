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

use Verifone\Payment\Helper\Path as Path;

class Config implements ConfigInterface
{
    const TEST_SUFFIX = 'demo';
    const SERVERS_AMOUNT = 3;

    /**
     * @var bool
     */
    protected $_isConfigSet = false;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var array
     */
    protected $_config = [];

    /**
     * @var \Magento\Framework\App\ProductMetadataInterface
     */
    protected $_productMetadata;

    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList
     */
    protected $_directoryList;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Verifone\Payment\Helper\Payment
     */
    protected $_helper;

    /**
     * Config constructor.
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Store\Model\StoreManagerInterface         $storeManager
     * @param \Magento\Framework\App\ProductMetadataInterface    $productMetadata
     * @param \Magento\Framework\App\Filesystem\DirectoryList    $directoryList
     * @param \Verifone\Payment\Helper\Payment                   $helper
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Verifone\Payment\Helper\Payment $helper
    ) {
        $this->_scopeConfig = $scopeConfig;
        $this->_productMetadata = $productMetadata;
        $this->_directoryList = $directoryList;
        $this->_storeManager = $storeManager;
        $this->_helper = $helper;
    }

    /**
     * @return bool
     */
    public function isConfigSet()
    {
        return $this->_isConfigSet;
    }

    public function getConfig($key = null)
    {

        if ($key && isset($this->_config[$key])) {
            return $this->_config[$key];
        } elseif ($key) {
            return null;
        }

        return $this->_config;
    }

    /**
     * @return bool
     */
    public function prepareConfig()
    {
        if (!$this->isConfigSet()) {

            $privateKeyPath = $this->_scopeConfig->getValue(Path::XML_PATH_KEY_SHOP);
            $publicKeyPath = $this->_scopeConfig->getValue(Path::XML_PATH_KEY_VERIFONE);
            $merchant = $this->_scopeConfig->getValue(Path::XML_PATH_MERCHANT_CODE);
            $software = 'Magento';
            $softwareVersion = $this->_productMetadata->getVersion();
            $currencyCode = $this->_storeManager->getStore()->getCurrentCurrency()->getCode();

            $currency = $this->_helper->convertCountryToISO4217($currencyCode);
            $rsaBlinding = $this->_scopeConfig->getValue(Path::XML_PATH_DISABLE_RSA_BLINDING);

            $this->_config = [
                'private-key' => $privateKeyPath,
                'public-key' => $publicKeyPath,
                'merchant' => $merchant,
                'software' => $software,
                'software-version' => $softwareVersion,
                'currency' => $currency,
                'rsa-blinding' => $rsaBlinding
            ];

            $this->_isConfigSet = true;
        }

        return true;
    }

    /**
     * @param string $filepath
     *
     * @return string
     */
    protected function _getFileFullPath($filepath)
    {
        if (file_exists($filepath)) {
            return $filepath;
        } else {
            return $this->_directoryList->getRoot() . DIRECTORY_SEPARATOR . $filepath;
        }
    }

    /**
     * @param $filepath
     *
     * @return string
     */
    public function getFileContent($filepath)
    {
        return file_get_contents($this->_getFileFullPath($filepath));
    }

    /**
     * @param $path
     *
     * @return array
     */
    protected function _prepareUrls($path)
    {
        if ($this->_scopeConfig->getValue(Path::XML_PATH_IS_LIVE_MODE)) {
            $urls = [];

            for ($i = 1; $i <= self::SERVERS_AMOUNT; $i++) {
                $urlPayment = $this->_scopeConfig->getValue($path . $i);
                if (!empty($urlPayment)) {
                    $urls[] = $urlPayment;
                }
            }

            return $urls;
        } else {
            return [$this->_scopeConfig->getValue($path . self::TEST_SUFFIX)];
        }
    }


}