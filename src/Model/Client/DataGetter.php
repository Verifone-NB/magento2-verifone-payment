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

namespace Verifone\Payment\Model\Client;

use Magento\Sales\Api\OrderItemRepositoryInterface;
use Verifone\Payment\Helper\Path;
use Verifone\Payment\Model\Config\Source\BasketItemSending;

class DataGetter
{

    const BASKET_LIMIT = 48;

    /**
     * @var \Verifone\Payment\Helper\Payment
     */
    protected $_helper;

    /**
     * @var \Verifone\Payment\Model\Session
     */
    protected $_session;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $_dateTime;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var \Magento\Framework\Locale\Resolver
     */
    protected $_resolver;

    /**
     * @var \Verifone\Payment\Model\Db\Payment\Method
     */
    protected $_paymentMethod;

    public function __construct(
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $dateTime,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Verifone\Payment\Helper\Payment $helper,
        \Verifone\Payment\Model\Session $session,
        \Verifone\Payment\Model\Db\Payment\Method $paymentMethod,
        \Magento\Framework\Locale\Resolver $resolver
    )
    {
        $this->_dateTime = $dateTime;
        $this->_scopeConfig = $scopeConfig;
        $this->_objectManager = $objectManager;
        $this->_resolver= $resolver;
        $this->_helper = $helper;
        $this->_session = $session;
        $this->_paymentMethod = $paymentMethod;
    }

    public function getOrderData(\Magento\Sales\Model\Order $order)
    {
        $incrementId = $order->getIncrementId();

        $time = $this->_dateTime->date(new \DateTime($order->getCreatedAt()))->format('Y-m-d H:i:s');

        $data = [
            'currency_code' => $this->_helper->convertCountryToISO4217($order->getOrderCurrencyCode()),
            'total_incl_amount' => $this->_getOrderGrossAmount($order),
            'total_excl_amount' => $this->_getOrderNetAmount($order),
            'total_vat' => $this->_getOrderVatAmount($order),
            'order_id' => $incrementId,
            'ext_order_id' => $order->getExtOrderId(),
            'time' => $time,
            'locale' => $this->_resolver->getLocale()
        ];

        $paymentMethod = $this->_session->getPaymentMethod();
        if ($paymentMethod) {
            $data['payment_method'] = $paymentMethod;
        }

        $paymentMethodId = $this->_session->getPaymentMethodId();
        if ($paymentMethodId) {
            $data['payment_method_id'] = $paymentMethodId;
        }

        $savePaymentMethod = $this->_session->isSavePaymentMethod();
        if ($savePaymentMethod) {
            $data['save_payment_method'] = true;
        }

        return $data;
    }

    public function getProductsData(\Magento\Sales\Model\Order $order)
    {

        $products = [];

        if (!$this->_isSendBasketItems()) {
            return $products;
        }

        $orderItems = $order->getAllVisibleItems();

        if ($order->getTotalItemCount() >= self::BASKET_LIMIT) {
            $orderItems = $this->_groupItemsByVat($orderItems);
        }

        $itemsTax = $itemsNetPrice = $itemsGrossPrice = 0;

        /**
         * @var $orderItem \Magento\Sales\Model\Order\Item
         */
        foreach ($orderItems as $orderItem) {
            $product = $this->_getBasketItemData($orderItem);

            if (!$this->_isCombineBasketItems()) {
                $products[] = $product;
            }

            $itemsTax += $product['gross_amount'] - $product['net_amount'];
            $itemsNetPrice += $product['net_amount'];
            $itemsGrossPrice += $product['gross_amount'];
        }

        if ($order->getShippingAmount() != 0) {
            $shipping = $this->getShippingData($order);

            if (!$this->_isCombineBasketItems()) {
                $products[] = $shipping;
            }

            $itemsTax += $shipping['gross_amount'] - $shipping['net_amount'];
            $itemsNetPrice += $shipping['net_amount'];
            $itemsGrossPrice += $shipping['gross_amount'];

        }

        if (!$this->_isCombineBasketItems()) {
            $discountAmount = $this->_getOrderGrossAmount($order) - $itemsGrossPrice;
            if (abs($discountAmount) >= 1) {
                $discountProduct = $this->_getBasketDiscountData($order, $this->_getOrderGrossAmount($order), $itemsGrossPrice,
                    $this->_getOrderNetAmount($order), $itemsNetPrice);

                $products[] = $discountProduct;
            }
        }

        if ($this->_isCombineBasketItems()) {
            $products[] = $this->_getCombinedBasketItemsData($order, $itemsGrossPrice, $itemsNetPrice);
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

            if ($this->_scopeConfig->getValue(Path::XML_PATH_EXTERNAL_CUSTOMER_ID)) {
                $customer['external_id'] = $order->getCustomer()->getData($this->_scopeConfig->getValue(Path::XML_PATH_EXTERNAL_CUSTOMER_ID_FIELD));
            }

            return $customer;
        }

        return null;
    }

    public function getShippingData(\Magento\Sales\Model\Order $order)
    {
        $shippingAmount = (float)$order->getShippingAmount();
        if ($shippingAmount) {

            $vat = 0;
            $gross = round($order->getShippingAmount(), 2) * 100;

            if ($order->getShippingInclTax()) {
                if ($order->getShippingInclTax() - $order->getShippingAmount() > 0) {
                    $vat = round(($order->getShippingInclTax() - $order->getShippingAmount()) / $order->getShippingAmount(),
                            2) * 100 * 100;
                }

                $gross = round($order->getShippingInclTax(), 2) * 100;
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

    /**
     * @param \Magento\Sales\Model\Order\Item $orderItem
     *
     * @return array
     */
    protected function _getBasketItemData(\Magento\Sales\Model\Order\Item $orderItem)
    {
        $itemCount = $orderItem->getQtyToInvoice() ? (int)$orderItem->getQtyToInvoice() : (int)$orderItem->getQtyOrdered();

        if ($orderItem->getProductType() == \Magento\Bundle\Model\Product\Type::TYPE_CODE) {
            $children = $orderItem->getChildrenItems();
            $taxes = [];
            /** @var \Magento\Sales\Model\Order\Item $child */
            foreach ($children as $child) {
                $taxes[] = $child->getTaxPercent();
            }

            $average = array_sum($taxes) / count($taxes);
            $orderItem->setTaxPercent($average);
        }

        return [
            'name' => $orderItem->getName(),
            'unit_count' => $itemCount,
            'unit_cost' => round($orderItem->getPrice(), 2) * 100,
            'net_amount' => round($orderItem->getPrice(), 2) * 100 * $itemCount,
            'gross_amount' => round($orderItem->getPriceInclTax(), 2) * 100 * $itemCount,
            'vat_percentage' => round($orderItem->getTaxPercent(), 2) * 100,
            'discount_percentage' => round($orderItem->getDiscountPercent()) * 100
        ];
    }

    protected function _getBasketDiscountData(
        \Magento\Sales\Model\Order $order,
        $orderGrossAmount,
        $itemsGrossPrice,
        $orderNetAmount,
        $itemsNetPrice
    )
    {


        return [
            'name' => $this->_getDiscountName($order),
            'unit_count' => 1,
            'unit_cost' => round($orderNetAmount - $itemsNetPrice, 0),
            'net_amount' => round($orderNetAmount - $itemsNetPrice, 0),
            'gross_amount' => round($orderGrossAmount - $itemsGrossPrice, 0),
            'vat_percentage' => round($orderNetAmount - $itemsNetPrice, 0) ?
                round(
                    (round($orderGrossAmount - $itemsGrossPrice, 0) - round($orderNetAmount - $itemsNetPrice, 0)
                    ) / round($orderNetAmount - $itemsNetPrice, 0), 2) * 100 * 100 : 0,
            'discount_percentage' => 0
        ];
    }

    protected function _getCombinedBasketItemsData(\Magento\Sales\Model\Order $order, $itemsGrossPrice, $itemsNetPrice)
    {
        // Must be "Tilaus %ORDERNUMBER%" according to docs
        $itemName = 'Tilaus ' . $order->getIncrementId();

        $grossAmount = $itemsGrossPrice;
        $netAmount = $itemsNetPrice;

        return [
            'name' => $itemName,
            'unit_count' => $order->getTotalItemCount(),
            'unit_cost' => $netAmount,
            'net_amount' => $netAmount,
            'gross_amount' => $grossAmount,
            'vat_percentage' => round(($grossAmount - $netAmount) / $netAmount, 2) * 100 * 100,
            'discount_percentage' => 0
        ];
    }

    /**
     * @param \Magento\Sales\Model\Order\Item[] $items
     *
     * @return \Magento\Sales\Model\Order\Item[]
     */
    protected function _groupItemsByVat($items)
    {
        /** @var \Magento\Sales\Model\Order\Item[] $result */
        $result = [];

        /** @var \Magento\Sales\Model\Order\Item $item */
        foreach ($items as $item) {
            $tax = (string)$item->getTaxPercent();

            if (!isset($result[$tax])) {
                /** @var \Magento\Sales\Model\Order\Item $mergedItem */
                $mergedItem = $this->_objectManager->create('\Magento\Sales\Model\Order\Item');
                $mergedItem->setName('Multiple items - vat ' . $tax);
                $mergedItem->setPrice(0);
                $mergedItem->setPriceInclTax(0);
                $mergedItem->setTaxPercent($item->getTaxPercent());
                $mergedItem->setQtyOrdered(1);

                $result[$tax] = $mergedItem;
            }

            $result[$tax]->setPrice($result[$tax]->getPrice() + $item->getRowTotal());
            $result[$tax]->setPriceInclTax($result[$tax]->getPriceInclTax() + $item->getRowTotalInclTax());
        }

        return $result;
    }

    protected function _getOrderGrossAmount(\Magento\Sales\Model\Order $order)
    {
        return round($order->getGrandTotal(), 2) * 100;
    }

    protected function _getOrderNetAmount(\Magento\Sales\Model\Order $order)
    {
        return $this->_getOrderGrossAmount($order) - $this->_getOrderVatAmount($order);
    }

    protected function _getOrderVatAmount(\Magento\Sales\Model\Order $order)
    {
        return round($order->getTaxAmount(), 2) * 100;
    }

    protected function _isSendBasketItems()
    {
        return $this->_isSendBasketItemsForAll()
            || ($this->_isSendBasketItemsForInvoice() && $this->_isMethodTypeInvoice());
    }

    /**
     * Verifone has an option to group basket items into 1 combined item.
     * Currently only possible to enable this for invoice payment methods.
     *
     * @return bool
     */
    protected function _isCombineBasketItems()
    {
        return ($this->_isCombineInvoiceBasketItems() && $this->_isMethodTypeInvoice());
    }

    /**
     * If true, basket items are sent for all orders and all payment methods.
     *
     * @return bool
     */
    protected function _isSendBasketItemsForAll()
    {
        $basketItemSending = $this->_scopeConfig->getValue(Path::XML_PATH_BASKET_ITEM_SENDING);

        return $basketItemSending == BasketItemSending::SEND_FOR_ALL;
    }

    /**
     * If true, basket items are sent for orders made with invoice payment methods.
     *
     * @return bool
     */
    protected function _isSendBasketItemsForInvoice()
    {
        $basketItemSending = $this->_scopeConfig->getValue(Path::XML_PATH_BASKET_ITEM_SENDING);

        return $basketItemSending == BasketItemSending::SEND_FOR_INVOICE;
    }

    protected function _isCombineInvoiceBasketItems()
    {
        return $this->_scopeConfig->getValue(Path::XML_PATH_COMBINE_INVOICE_BASKET_ITEMS);
    }

    protected function _isMethodTypeInvoice()
    {

        $paymentMethodCode = $this->_session->getPaymentMethod();

        if (!$paymentMethodCode) {
            return false;
        }

        $method = $this->_paymentMethod->loadByCode($paymentMethodCode);

        if (!$method->getId()) {
            return false;
        }

        return $method->isInvoice();
    }

    protected function _getDiscountName(\Magento\Sales\Model\Order $order)
    {

        if ($order->getDiscountDescription()) {
            return $order->getDiscountDescription();
        }

        return __('Discocunt')->getText();
    }
}