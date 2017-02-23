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

use Verifone\Core\DependencyInjection\Configuration\Backend\BackendConfigurationImpl;
use Verifone\Core\DependencyInjection\Configuration\Backend\GetAvailablePaymentMethodsConfigurationImpl;
use Verifone\Core\DependencyInjection\Service\TransactionImpl;
use Verifone\Core\Executor\BackendServiceExecutor;
use Verifone\Core\ExecutorContainer;
use Verifone\Core\ServiceFactory;

class RestClient extends \Verifone\Payment\Model\Client
{

    /**
     * @var \Verifone\Payment\Model\Client\Rest\Config
     */
    protected $_config;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        Rest\Config $config
    ) {
        parent::__construct($scopeConfig, $config);
    }

    public function orderRefund($order, $payment, $amount)
    {
        $result = $this->refund($order, $payment, $amount);

        if (!$result) {
            throw new \Exception('There was a problem while processing order refund request.');
        }

        return $result;
    }

    /**
     * @param null $merchant
     * @param null $publicKey
     * @param null $privateKey
     *
     * @return array|null
     */
    public function getPaymentMethods($merchant = null, $privateKey = null, $publicKey = null)/*: ?array*/
    {
        $config = $this->_config->getConfig();

        if (is_null($merchant)) {
            $merchant = $config['merchant'];
        }

        if (is_null($publicKey)) {
            $publicKey = $config['public-key'];
        }

        if (is_null($privateKey)) {
            $privateKey = $config['private-key'];
        }

        $publicKeyFile = $this->_config->getFileContent($publicKey);
        $privateKeyFile = $this->_config->getFileContent($privateKey);

        $configObject = new GetAvailablePaymentMethodsConfigurationImpl(
            $privateKeyFile,
            $merchant,
            $config['software'],
            $config['software-version'],
            $config['server-url'],
            $config['currency']
        );

        $service = ServiceFactory::createService($configObject, 'Backend\GetAvailablePaymentMethodsService');
        $container = new ExecutorContainer();

        /** @var BackendServiceExecutor $exec */
        $exec = $container->getExecutor('backend');
        $response = $exec->executeService($service, $publicKeyFile);

        if (!$response->getStatusCode()) {
            return null;
        }

        $body = $response->getBody();
        $methods = [];

        foreach ($body as $item) {
            $methods[] = $item->getCode();
        }

        return $methods;
    }

    public function refund(
        \Magento\Sales\Model\Order $order,
        \Magento\Payment\Model\InfoInterface $payment,
        $amount
    ) {
        $config = $this->_config->getConfig();

        $publicKeyFile = $this->_config->getFileContent($config['public-key']);
        $privateKeyFile = $this->_config->getFileContent($config['private-key']);

        $configObject = new BackendConfigurationImpl(
            $privateKeyFile,
            $config['merchant'],
            $config['software'],
            $config['software-version'],
            $config['server-url']
        );

        $refundAmount = $amount*100;

        $transaction = new TransactionImpl(
            $payment->getAdditionalInformation('payment-method'),
            $order->getExtOrderId(),
            (string) $refundAmount,
            $config['currency']
        );

        try {
            $service = ServiceFactory::createService($configObject, 'Backend\RefundPaymentService');
            $service->insertTransaction($transaction);

            $container = new ExecutorContainer();

            /** @var BackendServiceExecutor $exec */

            $exec = $container->getExecutor('backend');
            $response = $exec->executeService($service, $publicKeyFile);

            if($response->getStatusCode()) {
                return true;
            }
        } catch (\Exception $e) {
            var_dump($e->getMessage());
        }

        return false;

    }

}