<?php

namespace Verifone\Payment\Plugin\Sales\Model\Order\Email\Sender;

use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\Order;
use Verifone\Payment\Helper\Path;

class OrderSenderPlugin
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */

    protected $_scopeConfig;

    /**
     * OrderSenderPlugin constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    )
    {
        $this->_scopeConfig = $scopeConfig;
    }

    /**
     * @param OrderSender $sender
     * @param callable $proceed
     * @param Order $order
     * @param bool $forceSyncMode
     * @return bool
     */

    public function aroundSend(OrderSender $sender, callable $proceed, Order $order, $forceSyncMode = false)
    {
        $payment = $order->getPayment()->getMethodInstance()->getCode();

        if($payment == \Verifone\Payment\Model\Payment::CODE && $order->getStatus() != $this->_scopeConfig->getValue(Path::XML_PATH_ORDER_STATUS_PROCESSING)){
            return false;
        }

        return $proceed($order, $forceSyncMode);
    }
}