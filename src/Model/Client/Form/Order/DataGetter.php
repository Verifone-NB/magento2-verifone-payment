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

namespace Verifone\Payment\Model\Client\Form\Order;

use Verifone\Payment\Helper\Path;

class DataGetter extends \Verifone\Payment\Model\Client\DataGetter
{

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    public function __construct(
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $dateTime,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Verifone\Payment\Helper\Payment $helper,
        \Verifone\Payment\Model\Session $session,
        \Verifone\Payment\Model\Db\Payment\Method $paymentMethod,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Locale\Resolver $resolver
    )
    {
        parent::__construct(
            $dateTime,
            $scopeConfig,
            $objectManager,
            $helper,
            $session,
            $paymentMethod,
            $resolver
        );

        $this->_customerSession = $customerSession;
    }

    public function getCustomerData(\Magento\Sales\Model\Order $order = null)
    {
        if (!is_null($order)) {
            return parent::getCustomerData($order);
        }

        $billingAddress = $this->_customerSession->getCustomer()->getDefaultBillingAddress();
        /**
         * @var \Magento\Customer\Model\Customer $customer
         */
        $customer = $this->_customerSession->getCustomer();

        if ($billingAddress) {
            $customerData = [
                'firstname' => $customer->getFirstname(),
                'lastname' => $customer->getLastname(),
                'phone' => $billingAddress->getTelephone(),
                'email' => $customer->getEmail(),
                'locale' => $this->_resolver->getLocale()
            ];

            $addressData['line-1'] = $billingAddress->getStreetLine(1);
            $addressData['line-2'] = $billingAddress->getStreetLine(2) ?: $billingAddress->getRegion();
            $addressData['line-3'] = $billingAddress->getStreetLine(2) ? $billingAddress->getRegion(): '';
            $addressData['city'] = $billingAddress->getCity();
            $addressData['postal-code'] = $billingAddress->getPostcode();
            $addressData['country-code'] = $this->_helper->convertCountryCode2Numeric($billingAddress->getCountryId());
            $addressData['first-name'] = $billingAddress->getFirstname();
            $addressData['last-name'] = $billingAddress->getLastname();
            $addressData['phone-number'] = $billingAddress->getTelephone();
            $addressData['email'] = $billingAddress->getEmail();

            $customerData['address'] = $addressData;

            if ($this->_scopeConfig->getValue(Path::XML_PATH_EXTERNAL_CUSTOMER_ID)) {
                $customerData['external_id'] = $customer->getData($this->_scopeConfig->getValue(Path::XML_PATH_EXTERNAL_CUSTOMER_ID_FIELD));
            }

            return $customerData;
        }

        return null;
    }
}