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

use Verifone\Core\Converter\Response\CoreResponseConverter;
use Verifone\Core\DependencyInjection\CoreResponse\PaymentResponseImpl;
use Verifone\Core\DependencyInjection\Transporter\CoreResponse;
use Verifone\Core\Exception\ResponseCheckFailedException;
use Verifone\Payment\Helper\Path;
use Verifone\Payment\Model\Order\Exception;

class SaveMaskedPanNumber implements \Magento\Framework\Event\ObserverInterface
{

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var \Verifone\Payment\Helper\Order
     */
    protected $_order;

    /**
     * @var \Verifone\Payment\Model\ClientFactory
     */
    protected $_clientFactory;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Verifone\Payment\Helper\Order $order,
        \Verifone\Payment\Model\ClientFactory $clientFactory
    )
    {
        $this->_scopeConfig = $scopeConfig;
        $this->_order = $order;
        $this->_clientFactory = $clientFactory;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {

        if (!$this->_scopeConfig->getValue(Path::XML_PATH_SAVE_MASKED_PAN_NUMBER)) {
            return $this;
        }

        $success = $observer->getEvent()->getData('_success');
        if(!$success) {
            return $this;
        }

        $_response = $observer->getEvent()->getData('_response');

        /** @var \Verifone\Payment\Model\Client\FormClient $client */
        $client = $this->_clientFactory->create('frontend');

        /** @var string $orderNumber */
        $orderNumber = $client->getOrderNumber($_response);

        if(!$orderNumber) {
            return $this;
        }

        /**
         * @var \Magento\Sales\Model\Order $order
         */
        $order = $this->_order->loadOrderByIncrementId($orderNumber);

        try {
            /** @var CoreResponse $validate */
            $parsedResponse = $client->validateAndParseResponse($_response, $order);

            /** @var PaymentResponseImpl $body */
            $body = $parsedResponse->getBody();

            $validate = true;
        } catch (ResponseCheckFailedException $e) {
            $validate = false;
            $parsedResponse = null;
            $body = null;
        } catch (Exception $e) {
            $validate = false;
            $parsedResponse = null;
            $body = null;
        }

        if ($order->getId()
            && $validate
            && $parsedResponse->getStatusCode() == CoreResponseConverter::STATUS_OK
            && empty($body->getCancelMessage())
        ) {
            /** @var \Verifone\Core\DependencyInjection\CoreResponse\Interfaces\Card $card */
            $card = $body->getCard();
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