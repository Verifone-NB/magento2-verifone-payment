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


class updateCreditCard implements \Magento\Framework\Event\ObserverInterface
{

    /**
     * @var \Verifone\Payment\Helper\Saved
     */
    protected $_saved;

    public function __construct(
        \Verifone\Payment\Helper\Saved $saved
    )
    {
        $this->_saved = $saved;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {

        $success = $observer->getEvent()->getData('_success');
        if(!$success) {
            return $this;
        }

        $this->_saved->updatePaymentMethods();

        return $this;
    }
}