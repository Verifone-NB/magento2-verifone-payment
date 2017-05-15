<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is released under commercial license by Lamia Oy.
 *
 * @copyright Copyright (c) 2017 Lamia Oy (https://lamia.fi)
 * @author    Szymon Nosal <simon@lamia.fi>
 */


namespace Verifone\Payment\Plugin\Block\Adminhtml\Order;


class View
{

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $_urlBuilder;

    public function __construct(\Magento\Framework\UrlInterface $urlBuilder)
    {
        $this->_urlBuilder = $urlBuilder;
    }

    public function beforeGetOrderId(\Magento\Sales\Block\Adminhtml\Order\View $subject)
    {

        $order = $subject->getOrder();

        $paymentMethod = $order->getPayment()->getMethod();

        if($paymentMethod != \Verifone\Payment\Model\Payment::CODE) {
            return null;
        }

        $url = $this->_urlBuilder->getUrl('verifone_payment/sales_order/checkStatus', array(
            'order_id' => $order->getId(),
            'increment_id' => $order->getIncrementId()
        ));

        $subject->addButton(
            'verifone_payment_check_status',
            ['label' => __('Check payment status'), 'onclick' => 'setLocation("' . $url . '")'],
            -1
        );

        return null;
    }

}