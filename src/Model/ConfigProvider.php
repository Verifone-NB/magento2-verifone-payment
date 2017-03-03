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
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    public function __construct(
        \Magento\Payment\Helper\Data $paymentHelper,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Verifone\Payment\Model\Order\PaymentMethod $paymentMethod
    ) {
        $this->_paymentHelper = $paymentHelper;
        $this->_checkoutSession = $checkoutSession;
        $this->_payentMethodHelper = $paymentMethod;
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
            $config = [
                'payment' => [
                    'verifonePayment' => [
                        'redirectUrl' => $redirectUrl,
                        'paymentMethods' => $this->_payentMethodHelper->getPaymentMethods()
                    ]
                ]
            ];
        }
        return $config;
    }
}