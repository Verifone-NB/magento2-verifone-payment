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
namespace Verifone\Payment\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Verifone\Payment\Helper\Path;

class ConfigProvider implements ConfigProviderInterface
{
    /**
     * @var \Magento\Payment\Helper\Data
     */
    protected $_paymentHelper;

    /**
     * @var Order\PaymentMethod
     */
    protected $_payentMethodHelper;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Verifone\Payment\Helper\Saved
     */
    protected $_saved;

    public function __construct(
        \Magento\Payment\Helper\Data $paymentHelper,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Verifone\Payment\Model\Order\PaymentMethod $paymentMethod,
        \Verifone\Payment\Helper\Saved $saved
    ) {
        $this->_paymentHelper = $paymentHelper;
        $this->_checkoutSession = $checkoutSession;
        $this->_scopeConfig = $scopeConfig;
        $this->_payentMethodHelper = $paymentMethod;
        $this->_saved = $saved;
    }

    public function getConfig()
    {
        /**
         * @var $payment Payment
         */
        $config = [];
        $payment = $this->_paymentHelper->getMethodInstance(\Verifone\Payment\Model\Payment::CODE);
        if ($payment->isAvailable()) {
            $redirectUrl = $payment->getCheckoutRedirectUrl();
            $quote = $this->_checkoutSession->getQuote();
            $savedCC = $this->_saved->getSavedPayments();
            $config = [
                'payment' => [
                    'verifonePayment' => [
                        'redirectUrl' => $redirectUrl,
                        'paymentMethods' => $this->_payentMethodHelper->getPaymentMethods(),
                        'allowSaveCC' => $this->_payentMethodHelper->allowSaveCC() ? true : false,
                        'allowSaveCCInfo' => $this->_scopeConfig->getValue(Path::XML_PATH_REMEMBER_CC_INFO),
                        'savedPaymentMethods' => $savedCC,
                        'hasSavedPaymentsMethods' => count($savedCC) > 0 ? true : false
                    ]
                ]
            ];
        }
        return $config;
    }

}