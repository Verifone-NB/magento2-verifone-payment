<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is released under commercial license by Lamia Oy.
 *
 * @copyright Copyright (c) 2018 Lamia Oy (https://lamia.fi)
 */


namespace Verifone\Payment\Block\System\Config;


use Magento\Store\Model\ScopeInterface;
use Verifone\Payment\Helper\Path;
use Verifone\Payment\Model\Client\Config;

class Summary extends \Magento\Config\Block\System\Config\Form\Field
{
    protected $_buttonLabel = '';

    /** @var */
    protected $_config;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context, 
        Config $config,
        array $data = [])
    {
        parent::__construct($context, $data);
        
        $this->_config = $config;

        $this->_buttonLabel = __('Display configuration summary');
    }

    /**
     * Set Button Label
     *
     * @param string $buttonLabel
     *
     * @return \Verifone\Payment\Block\System\Config\Button\KeyGeneration
     */
    public function setButtonLabel($buttonLabel)
    {
        $this->_buttonLabel = $buttonLabel;
        return $this;
    }

    /**
     * @return \Magento\Framework\Phrase|string
     */
    public function getButtonLabel()
    {
        if ($this->_buttonLabel != __($this->_buttonLabel)) {
            // check if button label is already translated or not.
            return __($this->_buttonLabel);
        }

        return $this->_buttonLabel;
    }

    /**
     * Set template to itself
     *
     * @return \Verifone\Payment\Block\System\Config\Button\KeyGeneration
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if (!$this->getTemplate()) {
            $this->setTemplate('system/config/summary.phtml');
        }
        return $this;
    }

    /**
     * Unset some non-related element parameters
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     *
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * Get the button and scripts contents
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     *
     * @return string
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $originalData = $element->getOriginalData();
        $buttonLabel = !empty($originalData['button_label']) ? $originalData['button_label'] : $this->_buttonLabel;
        $this->addData(
            [
                'button_label' => $buttonLabel,
                'html_id' => $element->getHtmlId(),
            ]
        );

        return $this->_toHtml();
    }

    public function getWebsiteName()
    {
        if(!empty($this->_request->getParam('website'))){
            return $this->_storeManager->getWebsite($this->_request->getParam('website'))->getName();
        }

        return __('Default config');
    }

    public function getConfigurationData()
    {
        $data = array();

        if(!empty($this->_request->getParam('website'))){
            $code = $this->_storeManager->getWebsite($this->_request->getParam('website'))->getCode();
        } else {
            $code = null;
        }

        $data['isLiveMode'] = $this->_getScopeConfig(Path::XML_PATH_IS_LIVE_MODE, $code);
        $data['directory']['path'] = $this->_getScopeConfig(Path::XML_PATH_KEY_DIRECTORY, $code);
        $data['directory']['exists'] = file_exists($data['directory']['path']);
        $data['directory']['writable'] = is_writable($data['directory']['path']);

        $data['merchantCode'] = $this->_config->getMerchantAgreement($code);
        $data['isMerchantCodeDefault'] = $data['merchantCode'] === $this->_config->getMerchantAgreementDefault($code);

        /** Payment Service Key */
        $data['paymentServiceKey']['custom']['path'] = $this->_config->getPaypageKeyFileCustom($code);
        $data['paymentServiceKey']['custom']['exists'] = file_exists($data['paymentServiceKey']['custom']['path']);

        $data['paymentServiceKey']['default']['path'] = $this->_config->getPaypageKeyFileDefault($code);
        $data['paymentServiceKey']['default']['exists'] = file_exists($data['paymentServiceKey']['default']['path']);

        $data['paymentServiceKey']['path'] = $this->_config->getPaypageKeyFile($code);
        $data['paymentServiceKey']['isDefault'] = $data['paymentServiceKey']['path'] === $data['paymentServiceKey']['default']['path'];

        /** Shop Private Key */
        $data['shopPrivateKey']['custom']['path'] = $this->_config->getShopKeyFileCustom($code);
        $data['shopPrivateKey']['custom']['exists'] = file_exists($data['shopPrivateKey']['custom']['path']);

        $data['shopPrivateKey']['default']['path'] = $this->_config->getShopKeyFileDefault($code);
        $data['shopPrivateKey']['default']['exists'] = file_exists($data['shopPrivateKey']['default']['path']);

        $data['shopPrivateKey']['path'] = $this->_config->getShopKeyFile($code);
        $data['shopPrivateKey']['isDefault'] = $data['shopPrivateKey']['path'] === $data['shopPrivateKey']['default']['path'];

        /** Shop Public Key */
        $data['shopPublicKey']['custom']['path'] = $this->_config->getPublicShopKeyFileCustom($code);
        $data['shopPublicKey']['custom']['exists'] = file_exists($data['shopPublicKey']['custom']['path']);

        $data['shopPublicKey']['default']['path'] = $this->_config->getPublicShopKeyFileDefault($code);
        $data['shopPublicKey']['default']['exists'] = file_exists($data['shopPublicKey']['default']['path']);

        $data['shopPublicKey']['path'] = $this->_config->getPublicShopKeyFile($code);
        $data['shopPublicKey']['isDefault'] = $data['shopPublicKey']['path'] === $data['shopPublicKey']['default']['path'];
        $data['shopPublicKey']['content'] = $this->_config->getPublicShopKeyContent($code);

        return $data;
    }

    public function getConfigurationDataForDisplay()
    {
        $data = $this->getConfigurationData();

        /** Data for display */
        $display = array();

        $display['isLiveMode'] = array(
            'label' => __('Mode'),
            'value' => $data['isLiveMode'] ? __('Production') : __('Test')
        );

        $display['merchantCode'] = array(
            'label' => __('Verifone Payment merchant agreement code'),
            'value' => $data['merchantCode']
        );
        if($data['isMerchantCodeDefault']) {
            $display['merchantCode']['desc'] = __('Default test merchant agreement uses');
            $display['merchantCode']['desc_class'] = 'info';
        }

        $display['directory'] = array(
            'label' => __('Directory for store keys'),
            'value' => $data['directory']['path']
        );
        if($data['directory']['exists'] && $data['directory']['writable']) {
            $display['directory']['desc'] = __('Directory configured properly');
            $display['directory']['desc_class'] = 'success';
        } else {
            $display['directory']['desc'] = __('Problem with directory configuration. Please check configuration and save.');
            $display['directory']['desc_class'] = 'error';
        }

        $display['paymentServiceKey'] = array(
            'label' => __('Path and filename of Verifone Payment public key file'),
            'value' => $data['paymentServiceKey']['path']
        );
        if($data['paymentServiceKey']['isDefault']) {
            $display['paymentServiceKey']['desc'] = __('Default test key file uses');
            $display['paymentServiceKey']['desc_class'] = 'info';
        }

        $display['shopPrivateKey'] = array(
            'label' => __('Path and filename of shop private key file'),
            'value' => $data['shopPrivateKey']['path']
        );
        if($data['shopPrivateKey']['isDefault']) {
            $display['shopPrivateKey']['desc'] = __('Default test key file uses');
            $display['shopPrivateKey']['desc_class'] = 'info';
        }

        if($data['shopPrivateKey']['custom']['path'] !== null && !$data['shopPrivateKey']['custom']['exists']) {
            $display['shopPrivateKeyCustom'] = array(
                'label' => __(''),
                'value' => $data['shopPrivateKey']['custom']['path'],
                'desc' => __('Key file for provided merchant agreement code does not exist'),
                'desc_class' => 'error'
            );
        }

        $display['shopPublicKey'] = array(
            'label' => __('Path and filename of shop public key file'),
            'value' => $data['shopPublicKey']['path']
        );
        if($data['shopPublicKey']['isDefault']) {
            $display['shopPublicKey']['desc'] = __('Default test key file uses');
            $display['shopPublicKey']['desc_class'] = 'info';
        }

        if($data['shopPublicKey']['custom']['path'] !== null && !$data['shopPublicKey']['custom']['exists']) {
            $display['shopPublicKeyCustom'] = array(
                'label' => __(''),
                'value' => $data['shopPublicKey']['custom']['path'],
                'desc' => __('Key file for provided merchant agreement code does not exist'),
                'desc_class' => 'error'
            );
        }

        $display['shopPublicKeyContent'] = array(
            'label' => __('Public key file'),
            'value' => $data['shopPublicKey']['content'],
        );
        if($data['isLiveMode']) {
            $display['shopPublicKeyContent']['desc'] = __('Please, copy this key to payment operator configuration settings, otherwise, the payment will be broken.');
            $display['shopPublicKeyContent']['desc_class'] = 'success';
        }

        return $display;

    }

    protected function _getScopeConfig($path, $websiteCode)
    {
        if($websiteCode !== null) {
            return $this->_scopeConfig->getValue($path, ScopeInterface::SCOPE_WEBSITE, $websiteCode);
        }

        return $this->_scopeConfig->getValue($path);
    }
}