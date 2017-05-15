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


use Verifone\Core\DependencyInjection\CoreResponse\PaymentResponseImpl;
use Verifone\Payment\Helper\Path;

class SaveMaskedPanNumberRest implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var \Verifone\Payment\Helper\Order
     */
    protected $_order;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Verifone\Payment\Helper\Order $order
    )
    {
        $this->_scopeConfig = $scopeConfig;
        $this->_order = $order;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {

        if (!$this->_scopeConfig->getValue(Path::XML_PATH_SAVE_MASKED_PAN_NUMBER)) {
            return $this;
        }

        /** @var PaymentResponseImpl $_response */
        $_response = $observer->getEvent()->getData('_response');

        /**
         * @var \Magento\Sales\Model\Order $order
         */
        $order = $this->_order->loadOrderById($observer->getEvent()->getData('_orderId'));

        if ($order->getId()
            && empty($_response->getCancelMessage())
        ) {
            /** @var \Verifone\Core\DependencyInjection\CoreResponse\Interfaces\Card $card */
            $card = $_response->getCard();
            if (
                strlen($card->getFirst6()) &&
                $card->getFirst6() &&
                strlen($card->getLast2()) &&
                $card->getLast2()
            ) {
                $maskedPan = $card->getFirst6() . '********' . $card->getLast2();
                $order->setData('masked_pan_number', $maskedPan);
                $order->getResource()->save($order);
            }
        }

        return $this;
    }
}