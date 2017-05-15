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

namespace Verifone\Payment\Model\Checkout\PaymentInformationManagement;

class Plugin
{
    /**
     * @var \Verifone\Payment\Model\Session
     */
    protected $_session;

    /**
     * @param \Verifone\Payment\Model\Session $session
     */
    public function __construct(
        \Verifone\Payment\Model\Session $session
    ) {
        $this->_session = $session;
    }

    /**
     *
     * @param \Magento\Checkout\Model\PaymentInformationManagement $subject
     * @param $cartId
     * @param \Magento\Quote\Api\Data\PaymentInterface $paymentMethod
     */
    public function beforeSavePaymentInformationAndPlaceOrder(
        \Magento\Checkout\Model\PaymentInformationManagement $subject,
        $cartId,
        \Magento\Quote\Api\Data\PaymentInterface $paymentMethod,
        \Magento\Quote\Api\Data\AddressInterface $billingAddress = null
    ) {

        if ($paymentMethod->getMethod() === \Verifone\Payment\Model\Payment::CODE) {
            $additionalData = $paymentMethod->getAdditionalData();
            $this->_session->setPaymentMethod(isset($additionalData['payment-method']) ? $additionalData['payment-method'] : null);
            $this->_session->setPaymentMethodId(isset($additionalData['payment-method-id']) ? $additionalData['payment-method-id'] : null);
            $this->_session->setSavePaymentMethod(isset($additionalData['save-payment-method']) && $additionalData['save-payment-method'] ? true : false);
        }

    }
}