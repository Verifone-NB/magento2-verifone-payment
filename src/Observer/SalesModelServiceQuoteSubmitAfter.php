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

class SalesModelServiceQuoteSubmitAfter implements \Magento\Framework\Event\ObserverInterface
{

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $payment = $observer->getQuote()->getPayment();

        if ($payment['method'] == \Verifone\Payment\Model\Payment::CODE) {
            // Activate the quote
//            $observer->getQuote()->setIsActive(true);
        }
    }
}