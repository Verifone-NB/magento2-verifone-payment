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

namespace Verifone\Payment\Model\Client\Rest\Order;

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
                'email' => $customer->getEmail()
            ];

            if ($this->_scopeConfig->getValue(Path::XML_PATH_EXTERNAL_CUSTOMER_ID)) {
                $customerData['external_id'] = $customer->getData($this->_scopeConfig->getValue(Path::XML_PATH_EXTERNAL_CUSTOMER_ID_FIELD));
            }

            return $customerData;
        }

        return null;
    }
}