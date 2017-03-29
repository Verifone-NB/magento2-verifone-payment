<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is released under commercial license by Lamia Oy.
 *
 * @copyright Copyright (c) 2017 Lamia Oy (https://lamia.fi)
 * @author    Szymon Nosal <simon@lamia.fi>
 */

namespace Verifone\Payment\Observer;

use Verifone\Core\Configuration\FieldConfigImpl;
use Verifone\Core\DependencyInjection\Configuration\ConfigurationImpl;

class SalesQuotePaymentImportDataBefore implements \Magento\Framework\Event\ObserverInterface
{

    public function execute(\Magento\Framework\Event\Observer $observer)
    {

        $data = $observer->getInput();

        if($data['method'] != \Verifone\Payment\Model\Payment::CODE) {
            return $this;
        }

        $additionalData = $data['additional_data'];
        $_additional = array();

        if (isset($additionalData['save-payment-method']) && $additionalData['save-payment-method']) {
            $_additional = array_merge($_additional, array(
                'save-payment-method' => '1',
                FieldConfigImpl::PAYMENT_SAVE_METHOD => '1'
            ));
        }

        if (isset($additionalData['payment-method'])) {
            $_additional = array_merge($_additional, array(
                'payment-method-code' => $additionalData['payment-method'],
                FieldConfigImpl::PAYMENT_METHOD => $additionalData['payment-method']
            ));
        }

        if (isset($additionalData['saved-payment-method-id'])) {
            $_additional = array_merge($_additional, array(
                'saved-payment-method-id' => $additionalData['saved-payment-method-id'],
                FieldConfigImpl::PAYMENT_SAVED_METHOD_ID => $additionalData['saved-payment-method-id']
            ));
        }

        if (empty($_additional)) {
            return;
        }

        $observer
            ->getPayment()
            ->setAdditionalInformation($_additional);
    }
}