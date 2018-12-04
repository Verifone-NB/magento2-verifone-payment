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
use Verifone\Payment\Model\Config\Source\HandlingMode;

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
     * @var \Verifone\Payment\Helper\Keys
     */
    protected $_keysHelper;

    /**
     * Config constructor.
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\App\ProductMetadataInterface $productMetadata
     * @param \Magento\Framework\Module\Dir\Reader $reader
     * @param \Verifone\Payment\Helper\Payment $helper
     * @param \Verifone\Payment\Helper\Keys $keysHelper
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
        $this->_scopeConfig = $scopeConfig;
        $this->_productMetadata = $productMetadata;
        $this->_moduleReader = $reader;
        $this->_storeManager = $storeManager;
        $this->_helper = $helper;
        $this->_keysHelper = $keysHelper;
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

            $privateKeyPath = $this->getShopPrivateKeyFile();
            $publicKeyPath = $this->getPaymentPublicKeyFile();
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


    public function getKeyMode($code = null)
    {
        return $this->_getScopeConfig(Path::XML_PATH_KEY_MODE, $code);
    }

    public function isKeySimpleMode($code = null)
    {
        return (int)$this->getKeyMode($code) === HandlingMode::SIMPLE_MODE;
    }

    public function isKeyAdvancedMode($code = null)
    {
        return (int)$this->getKeyMode($code) === HandlingMode::ADVANCED_MODE;
    }


    /** KEYS FILES */
    public function isLiveMode($code = null)
    {
        return $this->_getScopeConfig(Path::XML_PATH_IS_LIVE_MODE, $code);
    }

    public function getKeysDirectory($code = null)
    {
        return $this->_getScopeConfig(Path::XML_PATH_KEY_DIRECTORY, $code);
    }

    public function getShopPrivateKeyFileName($code = null)
    {
        if ($this->isLiveMode($code)) {
            return $this->_getScopeConfig(Path::XML_PATH_KEY_SHOP, $code);
        }

        return $this->_getScopeConfig(Path::XML_PATH_KEY_SHOP_TEST, $code);
    }

    public function getLiveShopPrivateKeyPath($code = null)
    {

        if ($this->isKeySimpleMode($code)) {
            return null;
        }

        return $this->getKeysDirectory($code) . DIRECTORY_SEPARATOR . $this->_getScopeConfig(Path::XML_PATH_KEY_SHOP, $code);

    }

    public function getTestShopPrivateKeyPath($code = null)
    {

        if ($this->isKeySimpleMode($code)) {
            return null;
        }

        return $this->getKeysDirectory($code) . DIRECTORY_SEPARATOR . $this->_getScopeConfig(Path::XML_PATH_KEY_SHOP_TEST, $code);
    }

    public function getShopPrivateKeyPath($code = null)
    {
        if ($this->isLiveMode($code)) {
            return $this->getLiveShopPrivateKeyPath($code);
        }

        return $this->getTestShopPrivateKeyPath($code);
    }

    public function getShopPrivateKeyFile($code = null)
    {
        return $this->getShopPrivateKey();
    }

    public function getShopPrivateKey($code = null)
    {

        // If TEST mode is set
        if (!$this->isLiveMode($code)) {

            if ($this->getMerchantAgreement($code) === $this->getMerchantAgreementDefault($code)) {
                // If DEFAULT test merchant is set, return default key
                return $this->getShopPrivateKeyDefault($code);
            }

            if ($this->isKeySimpleMode($code)) {
                // If CUSTOM test merchant is set, and SIMPLE mode is set, return generated key stored in DB
                return $this->_keysHelper->getTestPrivateKey($code);
            }

            $path = $this->getTestShopPrivateKeyPath($code);
            if (file_exists($path)) {
                // If CUSTOM test merchant is set, and ADVANCED mode is set, return old key stored in files
                return file_get_contents($path);
            }

            // return default key file
            return $this->getShopPrivateKeyDefault($code);
        }

        // If LIVE mode is set

        if ($this->isKeySimpleMode($code)) {
            // If CUSTOM test merchant is set, and SIMPLE mode is set, return generated key stored in DB
            return $this->_keysHelper->getLivePrivateKey($code);
        }

        $path = $this->getLiveShopPrivateKeyPath($code);
        if (file_exists($path)) {
            // If CUSTOM test merchant is set, and ADVANCED mode is set, return old key stored in files
            return file_get_contents($path);
        }

        // return nothing
        return null;
    }

    public function getShopPrivateKeyPathDefault($code = null)
    {
        return $this->_moduleReader->getModuleDir('', 'Verifone_Payment') . DIRECTORY_SEPARATOR . 'keys' . DIRECTORY_SEPARATOR . $this->_getScopeConfig(Path::XML_PATH_KEY_SHOP_DEFAULT, $code);
    }

    public function getShopPrivateKeyDefault($code = null)
    {
        return file_get_contents($this->getShopPrivateKeyPathDefault($code));
    }

    public function getShopPublicKey($code = null)
    {
        // If TEST mode is set
        if (!$this->isLiveMode($code)) {

            if ($this->getMerchantAgreement($code) === $this->getMerchantAgreementDefault($code)) {
                // If DEFAULT test merchant is set, return default key
                // For default simple key is not require to configure in payment service.
                return null;
            }

            if ($this->isKeySimpleMode($code)) {
                // If CUSTOM test merchant is set, and SIMPLE mode is set, return generated key stored in DB
                return $this->_keysHelper->getTestPublicKey($code);
            }


            // If CUSTOM test merchant is set, and ADVANCED mode is set, return old key stored in files
            // This is not required, because if this mode is set, it means that payment service is configured,
            // and it does not require to configure again.
            return null;
        }

        // If LIVE mode is set
        if ($this->isKeySimpleMode($code)) {
            // If CUSTOM test merchant is set, and SIMPLE mode is set, return generated key stored in DB
            return $this->_keysHelper->getLivePublicKey($code);
        }

        // When advanced mode is set, or is default merchant agreement then in not require simple key to display.
        return null;
    }

    public function getShopPublicKeyPathDefault($code = null)
    {
        return $this->_moduleReader->getModuleDir('', 'Verifone_Payment') . DIRECTORY_SEPARATOR . 'keys' . DIRECTORY_SEPARATOR . $this->_getScopeConfig(Path::XML_PATH_KEY_SHOP_PUBLIC_DEFAULT, $code);
    }

    public function getShopPublicKeyDefault($code = null)
    {
        return file_get_contents($this->getShopPublicKeyPathDefault($code));
    }

    public function getPaymentPublicKeyPath($code = null)
    {
        if ($this->isLiveMode($code)) {
            return $this->_moduleReader->getModuleDir('', 'Verifone_Payment') . DIRECTORY_SEPARATOR . 'keys' . DIRECTORY_SEPARATOR . $this->_getScopeConfig(Path::XML_PATH_KEY_VERIFONE_LIVE_DEFAULT, $code);
        }

        return $this->_moduleReader->getModuleDir('', 'Verifone_Payment') . DIRECTORY_SEPARATOR . 'keys' . DIRECTORY_SEPARATOR . $this->_getScopeConfig(Path::XML_PATH_KEY_VERIFONE_TEST_DEFAULT, $code);
    }

    public function getPaymentPublicKeyFile($code = null)
    {
        return file_get_contents($this->getPaymentPublicKeyPath($code));
    }

    protected function _getScopeConfig($path, $websiteCode)
    {
        if ($websiteCode !== null) {
            return $this->_scopeConfig->getValue($path, ScopeInterface::SCOPE_WEBSITE, $websiteCode);
        }

        return $this->_scopeConfig->getValue($path);
    }

    public function getFileContent($string)
    {
        return $string;
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