<?php
/**
 *
 * NOTICE OF LICENSE
 *
 * This source file is released under commercial license by Lamia Oy.
 *
 * @copyright Copyright (c) 2017 Lamia Oy (https://lamia.fi)
 * @author    Szymon Nosal <simon@lamia.fi>
 */

namespace Verifone\Payment\Block\System\Config\Button;

use Magento\Config\Block\System\Config\Form\Field;

class RefreshPayments extends Field
{

    protected $_merchantAgreementCode = 'verifone_payment_merchant_agreement_code';
    protected $_shopPrivateKeyfile = 'verifone_payment_shop_private_keyfile';
    protected $_payPagePublicKeyfile = 'verifone_payment_pay_page_public_keyfile';

    protected $_buttonLabel = '';

    public function __construct(\Magento\Backend\Block\Template\Context $context, array $data = [])
    {
        parent::__construct($context, $data);

        $this->_buttonLabel = __('Get Available Payment Methods');
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
     * @return null
     */
    public function getPayPagePublicKeyfileField()
    {
        return $this->_payPagePublicKeyfile;
    }

    /**
     * @param null $payPagePublicKeyfile
     *
     * @return $this
     */
    public function setPayPagePublicKeyfileField($payPagePublicKeyfile)
    {
        $this->_payPagePublicKeyfile = $payPagePublicKeyfile;
        return $this;
    }

    /**
     * Set Button Label
     *
     * @param string $buttonLabel
     *
     * @return \Verifone\Payment\Block\System\Config\Button\RefreshPayments
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
        if(__($this->_buttonLabel) != $this->_buttonLabel) {
            // check if button label is already translated or not.
            return __($this->_buttonLabel);
        }

        return $this->_buttonLabel;
    }

    /**
     * Set template to itself
     *
     * @return \Verifone\Payment\Block\System\Config\Button\RefreshPayments
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if (!$this->getTemplate()) {
            $this->setTemplate('system/config/refresh.phtml');
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
                'ajax_url' => $this->_urlBuilder->getUrl('verifone_payment/system_config_payment/refresh'),
            ]
        );

        return $this->_toHtml();
    }
}