<?php
/**
 *
 * NOTICE OF LICENSE
 *
 * This source file is released under commercial license by Lamia Oy.
 *
 * @copyright  Copyright (c) 2017 Lamia Oy (https://lamia.fi)
 * @author     Szymon Nosal <simon@lamia.fi>
 *
 */

namespace Verifone\Payment\Model\Order;

class DataGetter
{

    /**
     * @var \Verifone\Payment\Helper\Payment
     */
    protected $_helper;

    public function __construct(
        \Verifone\Payment\Helper\Payment $helper
    ) {
        $this->_helper = $helper;
    }

    public function getOrderData(\Magento\Sales\Model\Order $order)
    {
        $incrementId = $order->getIncrementId();

        return [
            'currency_code' => $this->_helper->convertCountryToISO4217($order->getOrderCurrencyCode()),
            'total_incl_amount' => $this->getOrderGrossAmount($order),
            'total_excl_amount' => $this->getOrderNetAmount($order),
            'total_vat' => $this->getOrderVatAmount($order),
            'order_id' => $incrementId
        ];
    }

    public function getProductsData(\Magento\Sales\Model\Order $order)
    {
        /**
         * @var $orderItem \Magento\Sales\Model\Order\Item
         */
        $products = [];
        $orderItems = $order->getAllVisibleItems();
        foreach ($orderItems as $orderItem) {
            $itemCount = $orderItem->getQtyToInvoice() ? (int)$orderItem->getQtyToInvoice() : (int)$orderItem->getQtyOrdered();

            $products[] = [
                'name' => $orderItem->getName(),
                'unit_count' => $itemCount,
                'unit_cost' => round($orderItem->getPrice(), 2) * 100,
                'net_amount' => round($orderItem->getPrice(), 2) * 100 * $itemCount,
                'gross_amount' => round($orderItem->getPriceInclTax(), 2) * 100 * $itemCount,
                'vat_percentage' => round($orderItem->getTaxPercent(), 2) * 100,
                'discount_percentage' => round($orderItem->getDiscountPercent()) * 100
            ];
        }
        return $products;
    }

    public function getCustomerData(\Magento\Sales\Model\Order $order)
    {
        $billingAddress = $order->getBillingAddress();
        if ($billingAddress) {
            $customer = [
                'firstname' => $billingAddress->getFirstname(),
                'lastname' => $billingAddress->getLastname(),
                'phone' => $billingAddress->getTelephone(),
                'email' => $billingAddress->getEmail()
            ];

            return $customer;
        }

       return null;
    }

    public function getShippingData(\Magento\Sales\Model\Order $order)
    {
        $shippingAmount = (float)$order->getShippingAmount();
        if($shippingAmount) {

            $vat = 0;
            $gross = round($order->getShippingAmount(), 2) * 100;

            if($order->getShippingInclTax()) {
                if($order->getShippingInclTax() - $order->getShippingAmount() > 0){
                    $vat = round(($order->getShippingInclTax() - $order->getShippingAmount()) / $order->getShippingAmount(), 2) * 100 * 100;
                }

                $gross =  round($order->getShippingInclTax(), 2) * 100;
            }

            return [
                'name' => $order->getShippingDescription(),
                'unit_count' => 1,
                'unit_cost' => round($order->getShippingAmount(), 2) * 100,
                'net_amount' => round($order->getShippingAmount(), 2) * 100,
                'gross_amount' => $gross,
                'vat_percentage' => $vat,
                'discount_percentage' => 0
            ];
        }

        return null;
    }

    protected function getOrderGrossAmount(\Magento\Sales\Model\Order $order)
    {
        return round($order->getGrandTotal(), 2) * 100;
    }

    protected function getOrderNetAmount(\Magento\Sales\Model\Order $order)
    {
        return $this->getOrderGrossAmount($order) - $this->getOrderVatAmount($order);
    }

    protected function getOrderVatAmount(\Magento\Sales\Model\Order $order)
    {
        return round($order->getTaxAmount(), 2) * 100;
    }

}