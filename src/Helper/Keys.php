<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is released under commercial license by Lamia Oy.
 *
 * @copyright Copyright (c) 2018 Lamia Oy (https://lamia.fi)
 */


namespace Verifone\Payment\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Config\Scope;
use Magento\Store\Model\ScopeInterface;
use Verifone\Core\DependencyInjection\CryptUtils\RsaKeyGenerator;

class Keys extends AbstractHelper
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var \Magento\Framework\App\Config\Storage\WriterInterface
     */
    protected $_configWriter;

    public function __construct(
        Context $context,
        \Magento\Framework\App\Config\Storage\WriterInterface $configWriter
    )
    {
        parent::__construct($context);

        $this->_scopeConfig = $this->scopeConfig;
        $this->_configWriter = $configWriter;

    }

    /**
     * @param int|null $scopeId
     * @param string $mode
     * @param RsaKeyGenerator $keys
     * @return bool
     */
    public function storeKeysIntoDatabase($scopeId, $mode, $keys)
    {

        $scope = $scopeId === null ? ScopeConfigInterface::SCOPE_TYPE_DEFAULT : ScopeInterface::SCOPE_WEBSITES;

        $scopeId = $scopeId === null ? 0 : $scopeId;

        try {
            if ($mode === 'live') {
                $this->_configWriter->save('payment/verifone_payment/keys_live_public', $keys->getPublicKey(), $scope, $scopeId);
                $this->_configWriter->save('payment/verifone_payment/keys_live_private', $keys->getPrivateKey(), $scope, $scopeId);
            } else {
                $this->_configWriter->save('payment/verifone_payment/keys_test_public', $keys->getPublicKey(), $scope, $scopeId);
                $this->_configWriter->save('payment/verifone_payment/keys_test_private', $keys->getPrivateKey(), $scope, $scopeId);
            }
        } catch (\Exception $e) {
            return $e->getMessage();
        }

        return true;
    }

    public function getLivePublicKey($code)
    {
        return $this->_getScopeConfig('payment/verifone_payment/keys_live_public', $code);
    }

    public function getLivePrivateKey($code)
    {
        return $this->_getScopeConfig('payment/verifone_payment/keys_live_private', $code);
    }

    public function getTestPublicKey($code)
    {
        return $this->_getScopeConfig('payment/verifone_payment/keys_test_public', $code);
    }

    public function getTestPrivateKey($code)
    {
        return $this->_getScopeConfig('payment/verifone_payment/keys_test_private', $code);
    }

    protected function _getScopeConfig($path, $websiteCode)
    {
        if ($websiteCode !== null) {
            return $this->_scopeConfig->getValue($path, ScopeInterface::SCOPE_WEBSITE, $websiteCode);
        }

        return $this->_scopeConfig->getValue($path);
    }

}