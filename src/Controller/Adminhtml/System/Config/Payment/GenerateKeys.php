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
     * Refresh constructor.
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Verifone\Payment\Model\Client\Config $config
    )
    {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->_scopeConfig = $scopeConfig;
        $this->_config = $config;
        $this->_storeManager = $storeManager;
    }


    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $code = $this->_getWebsiteCode();

        $success = true;
        $messages = array();

        /**
         * @var \Magento\Framework\Controller\Result\Json $resultJson
         */
        $resultJson = $this->resultJsonFactory->create();

        // check configuration
        if (empty($this->_getScopeConfig(Path::XML_PATH_KEY_DIRECTORY, $code))) {
            $success = false;
            $messages[] = __('Please configure directory for store generated key.');
        }

        if ($this->_getScopeConfig(Path::XML_PATH_IS_LIVE_MODE, $code)) {
            if(empty($this->_config->getMerchantAgreement($code))) {
                $success = false;
                $messages[] = __('Please provide merchant agreement code');
            }

        } else {
            if(empty($this->_config->getMerchantAgreement($code))) {
                $success = false;
                $messages[] = __('Please provide merchant agreement code for the test');
            }

            if($this->_config->getMerchantAgreement($code) === $this->_config->getMerchantAgreementDefault($code)) {
                $success = false;
                $messages[] = __('You can not generate keys for default test merchant agreement');
            }
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

        if ($resultGenerate === true) {

            return $resultJson->setData(
                [
                    'valid' => true,
                    'messages' => [__('Keys are generated correctly. Please refresh page.')]
                ]
            );

        }

        $messages[] = __('Problem with generating new keys.');

        return $resultJson->setData(
            [
                'valid' => false,
                'messages' => $messages
            ]
        );
    }

    protected function _generateKeys()
    {
        $code = $this->_getWebsiteCode();
        $directory = $this->_getScopeConfig(Path::XML_PATH_KEY_DIRECTORY, $code);

        if (!is_writable($directory)) {
            return __('Problem with save keys into directory. Please check configuration.');
        }

        $merchant = $this->_config->getMerchantAgreement($code);

        $prefix = $directory . DIRECTORY_SEPARATOR . $merchant;

        $generator = new RsaKeyGenerator();
        $result = $generator->generate();

        if(!$result) {
            return __('Problem with generate new keys.');
        }

        if (\file_put_contents($prefix . '-private.pem', $generator->getPrivateKey()) === false ||
            \file_put_contents($prefix . '-public.pem', $generator->getPublicKey()) === false) {

            unlink($prefix . '-private.pem');
            unlink($prefix . '-public.pem');

            return __('Problem with save keys into directory. Please check configuration.');

        }

        return true;
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

    protected function _getScopeConfig($path, $websiteCode)
    {
        if ($websiteCode !== null) {
            return $this->_scopeConfig->getValue($path, ScopeInterface::SCOPE_WEBSITE, $websiteCode);
        }

        return $this->_scopeConfig->getValue($path);
    }
}