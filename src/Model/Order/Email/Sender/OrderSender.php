<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is released under commercial license by Lamia Oy.
 *
 * @copyright Copyright (c) 2017 Lamia Oy (https://lamia.fi)
 * @author    Szymon Nosal <simon@lamia.fi>
 */


namespace Verifone\Payment\Model\Order\Email\Sender;

use Magento\Sales\Model\Order;
use Verifone\Payment\Helper\Path;

class OrderSender extends \Magento\Sales\Model\Order\Email\Sender\OrderSender
{
    /**
     * Sends order email to the customer.
     *
     * Email will be sent immediately in two cases:
     *
     * - if asynchronous email sending is disabled in global settings
     * - if $forceSyncMode parameter is set to TRUE
     *
     * Otherwise, email will be sent later during running of
     * corresponding cron job.
     *
     * @param Order $order
     * @param bool $forceSyncMode
     * @return bool
     */
    public function send(Order $order, $forceSyncMode = false)
    {

        $payment = $order->getPayment()->getMethodInstance()->getCode();

        if($payment == \Verifone\Payment\Model\Payment::CODE && $order->getStatus() != $this->globalConfig->getValue(Path::XML_PATH_ORDER_STATUS_PROCESSING)){
            return false;
        }

        return parent::send($order, $forceSyncMode);
    }
}