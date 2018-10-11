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

use Magento\Store\Model\ScopeInterface;
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
     * @var \Magento\Framework\Module\Dir\Reader
     */
    protected $_moduleReader;

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
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\App\ProductMetadataInterface $productMetadata
     * @param \Magento\Framework\Module\Dir\Reader $reader
     * @param \Verifone\Payment\Helper\Payment $helper
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        \Magento\Framework\Module\Dir\Reader $reader,
        \Verifone\Payment\Helper\Payment $helper
    )
    {
        $this->_scopeConfig = $scopeConfig;
        $this->_productMetadata = $productMetadata;
        $this->_moduleReader = $reader;
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

            $privateKeyPath = $this->getShopKeyFile();
            $publicKeyPath = $this->getPaypageKeyFile();
            $merchant = $this->getMerchantAgreement();
            $software = 'Magento';
            $softwareVersion = $this->_productMetadata->getVersion();
            $currencyCode = $this->_storeManager->getStore()->getCurrentCurrency()->getCode();

            $currency = $this->_helper->convertCountryToISO4217($currencyCode);
            $rsaBlinding = $this->_scopeConfig->getValue(Path::XML_PATH_DISABLE_RSA_BLINDING);

            $saveMaskedPan = $this->_scopeConfig->getValue(Path::XML_PATH_SAVE_MASKED_PAN_NUMBER);

            $checkNodeAvailability = $this->_scopeConfig->getValue(Path::XML_PATH_VALIDATE_URL);

            $styleCode = $this->_scopeConfig->getValue(Path::XML_PATH_STYLE_CODE);

            $this->_config = [
                'private-key' => $privateKeyPath,
                'public-key' => $publicKeyPath,
                'merchant' => $merchant,
                'software' => $software,
                'software-version' => $softwareVersion,
                'currency' => $currency,
                'rsa-blinding' => $rsaBlinding,
                'save-masked-pan' => $saveMaskedPan,
                'check-node-availability' => $checkNodeAvailability,
                'style-code' => $styleCode === null ? '' : $styleCode
            ];

            $this->_isConfigSet = true;
        }

        return true;
    }

    public function getMerchantAgreement($code = null)
    {

        if ($this->_getScopeConfig(Path::XML_PATH_IS_LIVE_MODE, $code)) {
            return $this->_getScopeConfig(Path::XML_PATH_MERCHANT_CODE, $code);
        }

        if (!empty($this->_getScopeConfig(Path::XML_PATH_MERCHANT_CODE_TEST, $code))) {
            return $this->_getScopeConfig(Path::XML_PATH_MERCHANT_CODE_TEST, $code);
        }

        return $this->_getScopeConfig(Path::XML_PATH_MERCHANT_CODE_DEFAULT, $code);

    }

    public function getMerchantAgreementDefault($code = null)
    {
        return $this->_getScopeConfig(Path::XML_PATH_MERCHANT_CODE_DEFAULT, $code);
    }

    ///
    /// Shop Private Key
    ///
    public function getShopKeyFile($code = null)
    {
        $customKey = $this->getShopKeyFileCustom($code);

        if (null !== $customKey && file_exists($customKey)) {
            return $customKey;
        }

        return $this->getShopKeyFileDefault();
    }

    public function getShopKeyFileCustom($code = null)
    {

        $key = $this->getKeyCustom(Path::XML_PATH_KEY_SHOP);

        if (($key === null || !file_exists($key)) && $this->getMerchantAgreement($code) !== $this->getMerchantAgreementDefault($code)) {
            $directory = $this->_getScopeConfig(Path::XML_PATH_KEY_DIRECTORY, $code);
            $fileName = $this->getMerchantAgreement($code) . '-private.pem';

            return $directory . DIRECTORY_SEPARATOR . $fileName;
        } elseif ($key !== null && $this->getMerchantAgreement($code) === $this->getMerchantAgreementDefault($code)) {
            return null;
        }

        return $key;
    }

    public function getShopKeyFileDefault($code = null)
    {
        return $this->getKeyDefault(Path::XML_PATH_KEY_SHOP_DEFAULT);
    }

    ///
    /// Shop Public Key
    ///
    public function getPublicShopKeyFile($code = null)
    {
        $customKey = $this->getPublicShopKeyFileCustom($code);

        if (null !== $customKey && file_exists($customKey)) {
            return $customKey;
        }

        return $this->getPublicShopKeyFileDefault();
    }

    public function getPublicShopKeyFileCustom($code = null)
    {
        if ($this->getMerchantAgreement($code) !== $this->getMerchantAgreementDefault($code)) {
            $directory = $this->_getScopeConfig(Path::XML_PATH_KEY_DIRECTORY, $code);

            $fileName = $this->getMerchantAgreement($code) . '-public.pem';
            return $directory . DIRECTORY_SEPARATOR . $fileName;
        }

        return null;

    }

    public function getPublicShopKeyFileDefault($code = null)
    {
        return $this->getKeyDefault(Path::XML_PATH_KEY_SHOP_PUBLIC_DEFAULT);
    }

    public function getPublicShopKeyContent($code = null)
    {
        return \file_get_contents($this->getPublicShopKeyFile($code));
    }

    ///
    /// Payment Service Public Key
    ///
    public function getPaypageKeyFile($code = null)
    {
        $customKey = $this->getPaypageKeyFileCustom($code, $code);

        if (null !== $customKey && file_exists($customKey)) {
            return $customKey;
        }

        if ($this->_getScopeConfig(Path::XML_PATH_IS_LIVE_MODE, $code)) {
            return $this->getPaypageKeyFileLiveDefault($code);
        }

        return $this->getPaypageKeyFileDefault();
    }

    public function getPaypageKeyFileCustom($code = null)
    {
        return $this->getKeyCustom(PATH::XML_PATH_KEY_VERIFONE);
    }

    public function getPaypageKeyFileLiveDefault($code = null)
    {
        return $this->getKeyDefault(Path::XML_PATH_KEY_VERIFONE_LIVE_DEFAULT);
    }

    public function getPaypageKeyFileDefault($code = null)
    {
        return $this->getKeyDefault(Path::XML_PATH_KEY_VERIFONE_TEST_DEFAULT);
    }

    public function getKeyCustom($keyConfigurationPath, $code = null)
    {
        $directory = $this->_getScopeConfig(Path::XML_PATH_KEY_DIRECTORY, $code);

        $fileName = '';

        if ($this->_getScopeConfig(Path::XML_PATH_IS_LIVE_MODE, $code)) {
            $fileName = $this->_getScopeConfig($keyConfigurationPath, $code);
        } else {
            if (!empty($this->_getScopeConfig($keyConfigurationPath . '_test', $code))) {
                $fileName = $this->_getScopeConfig($keyConfigurationPath . '_test', $code);
            }
        }

        if (empty($fileName)) {
            return null;
        }

        return $directory . DIRECTORY_SEPARATOR . $fileName;
    }

    public function getKeyDefault($keyConfigurationPath, $code = null)
    {
        $moduleDir = $this->_moduleReader->getModuleDir('', 'Verifone_Payment');
        return $moduleDir . DIRECTORY_SEPARATOR . 'keys' . DIRECTORY_SEPARATOR . $this->_getScopeConfig($keyConfigurationPath, $code);
    }

    protected function _getScopeConfig($path, $websiteCode)
    {
        if ($websiteCode !== null) {
            return $this->_scopeConfig->getValue($path, ScopeInterface::SCOPE_WEBSITE, $websiteCode);
        }

        return $this->_scopeConfig->getValue($path);
    }

    /**
     * @param $filepath
     *
     * @return string
     */
    public function getFileContent($filepath)
    {
        return file_get_contents($filepath);
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