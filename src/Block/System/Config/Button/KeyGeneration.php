<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is released under commercial license by Lamia Oy.
 *
 * @copyright Copyright (c) 2018 Lamia Oy (https://lamia.fi)
 */


namespace Verifone\Payment\Block\System\Config\Button;

use Magento\Config\Block\System\Config\Form\Field;

class KeyGeneration extends Field
{
    protected $_merchantAgreementCode = 'verifone_payment_merchant_agreement_code';
    protected $_shopPrivateKeyfile = 'verifone_payment_shop_private_keyfile';

    protected $_isLiveMode = 'verifone_payment_is_live_mode';

    protected $_buttonLabel = '';

    public function __construct(\Magento\Backend\Block\Template\Context $context, array $data = [])
    {
        parent::__construct($context, $data);

        $this->_buttonLabel = __('Generate new keys');
    }

    /**
     * @return null
     */
    public function getMerchantAgreementCodeField()
    {
        return $this->_merchantAgreementCode;
    }

    /**
     * @param null $merchantAgreementCode
     *
     * @return $this
     */
    public function setMerchantAgreementCodeField($merchantAgreementCode)
    {
        $this->_merchantAgreementCode = $merchantAgreementCode;
        return $this;
    }

    /**
     * @return null
     */
    public function getShopPrivateKeyfileField()
    {
        return $this->_shopPrivateKeyfile;
    }

    /**
     * @param null $shopPrivateKeyfile
     *
     * @return $this
     */
    public function setShopPrivateKeyfileField($shopPrivateKeyfile)
    {
        $this->_shopPrivateKeyfile = $shopPrivateKeyfile;
        return $this;
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
        if($this->_buttonLabel != __($this->_buttonLabel)) {
            // check if button label is already translated or not.
            return __($this->_buttonLabel);
        }

        return $this->_buttonLabel;
    }

    public function getConfirmationMessage()
    {
        $msg = "Are you sure you want to generate keys? The private key will be saved in location from configuration field: %s. \\n\\nAfter creating a new key, remember to copy this key to payment operator configuration settings, otherwise the payment will be broken";
        $field = __('Shop private key filename');

        return sprintf($msg, $field);
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
            $this->setTemplate('system/config/generate_key.phtml');
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

    public function getWebsiteId()
    {
        return $this->_request->getParam('website', 0);
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
                'ajax_url' => $this->_urlBuilder->getUrl('verifone_payment/system_config_payment/generateKeys'),
            ]
        );

        return $this->_toHtml();
    }
}