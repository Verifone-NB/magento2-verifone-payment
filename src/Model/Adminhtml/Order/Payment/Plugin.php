<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is released under commercial license by Lamia Oy.
 *
 * @copyright Copyright (c) 2017 Lamia Oy (https://lamia.fi)
 * @author    Szymon Nosal <simon@lamia.fi>
 */


namespace Verifone\Payment\Model\Adminhtml\Order\Payment;

class Plugin
{

    /** @var \Verifone\Payment\Model\Db\Payment\Method  */
    protected $_method;

    public function __construct(
        \Verifone\Payment\Model\Db\Payment\Method $method
    )
    {
        $this->_method = $method;
    }

    public function afterGetPaymentHtml($subject, $result)
    {
        /** @var \Magento\Sales\Model\Order $payment */
        $order = $subject->getOrder();
        $payment = $order->getPayment();

        if($payment->getMethodInstance()->getCode() != \Verifone\Payment\Model\Payment::CODE) {
            return $result;
        }

        $methodCode = $order->getData('payment_method_code');

        return $result . ' [' . $this->_getMethodLabel($methodCode) .']';
    }

    protected function _getMethodLabel($code)
    {
        $method = $this->_method->loadByCode($code);

        if(!$method) {
            return '';
        }

        return $method->getName();

    }

}