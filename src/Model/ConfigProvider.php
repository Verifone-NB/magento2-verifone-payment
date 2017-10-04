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
    )
    {
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
            $savedCC = $this->_saved->getSavedPayments(true);

            $paymentMethods = $this->_payentMethodHelper->getPaymentMethods();

            if($this->_payentMethodHelper->allowSaveCC()) {
                foreach ($paymentMethods as $key => $methods) {
                    if($methods['isCard']) {
                        $paymentMethods[$key]['methods'] = array_merge($this->_prepareSavedCards($savedCC), $paymentMethods[$key]['methods']);
                        break;
                    }
                }
            }

            $ccInfo = '';
            if(strlen($this->_scopeConfig->getValue(Path::XML_PATH_REMEMBER_CC_INFO)) > 0) {
                $ccInfo = $this->_scopeConfig->getValue(Path::XML_PATH_REMEMBER_CC_INFO);
            }

            $config = [
                'payment' => [
                    'verifonePayment' => [
                        'redirectUrl' => $redirectUrl,
                        'paymentMethods' => $paymentMethods,
                        'allowSaveCC' => $this->_payentMethodHelper->allowSaveCC() ? true : false,
                        'allowSaveCCInfo' => $ccInfo,
                        'savedPaymentMethods' => $this->_prepareSavedCards($savedCC),
                        'hasSavedPaymentMethods' => count($savedCC) > 0 ? true : false
                    ]
                ]
            ];
        }

        return $config;
    }

    protected function _prepareSavedCards($saved)
    {
        $cards = [];

        foreach ($saved as $card) {
            $tmp = [
                'id' => $card['card-method-id'],
                'value' => $card['card-method-id'],
                'label' => $card['card-method-title'],
                'code' => $card['card-method-code']
            ];

            if($card['is_default']) {
                array_unshift($cards, $tmp);
            } else {
                array_push($cards, $tmp);
            }
        }

        return $cards;
    }
}