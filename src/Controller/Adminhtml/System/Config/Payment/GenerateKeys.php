<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is released under commercial license by Lamia Oy.
 *
 * @copyright Copyright (c) 2018 Lamia Oy (https://lamia.fi)
 */


namespace Verifone\Payment\Controller\Adminhtml\System\Config\Payment;


use Magento\Store\Model\ScopeInterface;
use Verifone\Core\DependencyInjection\CryptUtils\RsaKeyGenerator;
use Verifone\Payment\Helper\Path;

class GenerateKeys extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Verifone\Payment\Model\Client\Config
     */
    protected $_config;

    /**
     * @var \Verifone\Payment\Helper\Keys
     */
    protected $_keyHelper;

    protected $_cacheTypeList;

    protected $_cacheFrontendPool;

    /**
     * Refresh constructor.
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Verifone\Payment\Model\Client\Config $config
     * @param \Verifone\Payment\Helper\Keys $keys
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param \Magento\Framework\App\Cache\Frontend\Pool $cacheFrontendPool
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Verifone\Payment\Model\Client\Config $config,
        \Verifone\Payment\Helper\Keys $keys,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\App\Cache\Frontend\Pool $cacheFrontendPool
    )
    {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->_scopeConfig = $scopeConfig;
        $this->_config = $config;
        $this->_storeManager = $storeManager;
        $this->_keyHelper = $keys;
        $this->_cacheTypeList = $cacheTypeList;
        $this->_cacheFrontendPool = $cacheFrontendPool;
    }


    public function execute()
    {

        $success = true;
        $messages = [];

        $code = $this->_getWebsiteCode();
        $mode = $this->getRequest()->getParam('mode');

        /**
         * @var \Magento\Framework\Controller\Result\Json $resultJson
         */
        $resultJson = $this->resultJsonFactory->create();

        if (empty($mode)) {
            $success = false;
            $messages[] = __('Problem with generating new keys. ') . __('Please refresh the page and try again.');
        }

        if (!$success) {
            return $resultJson->setData(
                [
                    'valid' => false,
                    'messages' => $messages
                ]
            );
        }

        $resultGenerate = $this->_generateKeys();

        if ($resultGenerate !== false) {
            $resultStoreKey = $this->_keyHelper->storeKeysIntoDatabase($this->_getScopeId(), $mode, $resultGenerate);

            if ($resultStoreKey === true) {
                $messages[] = __('Keys are generated correctly. Please refresh the page.');

                $types = array('config', 'layout', 'block_html');
                foreach ($types as $type) {
                    $this->_cacheTypeList->cleanType($type);
                }
                foreach ($this->_cacheFrontendPool as $cacheFrontend) {
                    $cacheFrontend->getBackend()->clean();
                }

                return $resultJson->setData(
                    [
                        'valid' => true,
                        'messages' => $messages
                    ]
                );
            }

            $success = false;
            $messages[] = $resultStoreKey;

        } else {
            $success = false;
            $messages[] = __('Problem with generating new keys.');
        }

        return $resultJson->setData(
            [
                'valid' => $success,
                'messages' => $messages
            ]
        );

    }

    protected function _generateKeys()
    {
        $generator = new RsaKeyGenerator();
        $result = $generator->generate();

        if (!$result) {
            return false;
        }

        return $generator;
    }

    protected function _getWebsiteCode()
    {
        $websiteId = $this->_request->getParam('website');
        if ($websiteId !== null && (string)$websiteId !== '0') {
            $code = $this->_storeManager->getWebsite($websiteId)->getCode();
        } else {
            $code = null;
        }

        return $code;
    }

    protected function _getScopeId()
    {
        $websiteId = $this->_request->getParam('website');
        if ($websiteId !== null && (string)$websiteId !== '0') {
            return $websiteId;
        }

        return null;
    }

    protected function _getScopeConfig($path, $websiteCode)
    {
        if ($websiteCode !== null) {
            return $this->_scopeConfig->getValue($path, ScopeInterface::SCOPE_WEBSITE, $websiteCode);
        }

        return $this->_scopeConfig->getValue($path);
    }
}