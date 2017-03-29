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

use Verifone\Payment\Helper\Path;
use Verifone\Payment\Model\Db\Payment\Method;

class PaymentMethod
{

    const DEFAULT_CODE = 'VerifonePayment';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var \Verifone\Payment\Model\ClientFactory
     */
    protected $_clientFactory;

    /**
     * @var \Verifone\Payment\Model\Db\Payment\MethodFactory
     */
    protected $_methodFactory;

    /**
     * PaymentMethod constructor.
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Verifone\Payment\Model\ClientFactory              $clientFactory
     * @param \Verifone\Payment\Model\Db\Payment\MethodFactory   $methodFactory
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Verifone\Payment\Model\ClientFactory $clientFactory,
        \Verifone\Payment\Model\Db\Payment\MethodFactory $methodFactory
    ) {
        $this->_scopeConfig = $scopeConfig;
        $this->_clientFactory = $clientFactory;
        $this->_methodFactory = $methodFactory;
    }

    /**
     * Returns array of payment methods.
     *
     * @return array
     */
    public function getPaymentMethods()
    {
        /** @var \Verifone\Payment\Model\Client\FormClient $client */
        $client = $this->_clientFactory->create('frontend');
        $methods = $client->getPaymentMethods();

        $model = $this->_methodFactory->create();

        foreach ($methods as $key => $method) {

            foreach ($method['payments'] as $i => $code) {

                /** @var Method $method */
                $method = $model->loadByCode($code);

                if (!$method->isActive()) {
                    continue;
                }

                $tmp = [];

                $tmp['id'] = 'verifone-payment-method-' . $code;
                $tmp['value'] = $code;
                $tmp['label'] = $method->getName();

                $methods[$key]['methods'][] = $tmp;

            }

        }

        return $methods;
    }

    public function allowSaveCC()
    {
        return $this->_scopeConfig->getValue(Path::XML_PATH_ALLOW_TO_SAVE_CC);
    }

    /**
     * @param string|null $merchant
     * @param string|null $privateKey
     * @param string|null $publicKey
     *
     * @return \Verifone\Payment\Model\ResourceModel\Db\Payment\Method\Collection|string
     */
    public function refreshPaymentMethods($merchant = null, $privateKey = null, $publicKey = null)
    {
        /** @var \Verifone\Payment\Model\Client\RestClient $client */
        $client = $this->_clientFactory->create('backend');
        $paymentMethods = $client->getPaymentMethods($merchant, $privateKey, $publicKey);

        array_push($paymentMethods, self::DEFAULT_CODE);

        $result = $this->_methodFactory->create()->refreshMethods($paymentMethods);

        return $result;
    }
}