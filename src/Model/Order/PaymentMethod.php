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

class PaymentMethod
{
    /**
     * @var \Verifone\Payment\Model\ClientFactory
     */
    protected $_clientFactory;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * Paytype constructor.
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Verifone\Payment\Model\ClientFactory              $clientFactory
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Verifone\Payment\Model\ClientFactory $clientFactory
    ) {
        $this->_scopeConfig = $scopeConfig;
        $this->_clientFactory = $clientFactory;
    }

    /**
     * Returns false if paytypes are disabled in checkout or there is no method for paytypes in current API.
     * Returns array of paytypes otherwise.
     *
     * @return array
     */
    public function getPaymentMethods()
    {
        /** @var \Verifone\Payment\Model\Client\FormClient $client */
        $client = $this->_clientFactory->create('frontend');
        $methods = $client->getPaymentMethods();

        foreach ($methods as $key => $method) {
            //if (!$paytype['active']) {
            //    unset($paytypes[$key]);
            //} else {
            $methods[$key]['id'] = 'verifone-payment-paytype-' . $method['code'];
            //}
        }
        return $methods;
    }

    /**
     * @param string|null $merchant
     * @param string|null $privateKey
     * @param string|null $publicKey
     *
     * @return array|null
     */
    public function refreshPaymentMethods($merchant = null, $privateKey = null, $publicKey = null)
    {
        /** @var \Verifone\Payment\Model\Client\Rest $client */
        $client = $this->_clientFactory->create('backend');
        $paymentMethods = $client->getPaymentMethods($merchant, $privateKey, $publicKey);

        return $paymentMethods;
    }
}