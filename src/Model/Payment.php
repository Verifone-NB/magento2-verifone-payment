<?php
/**
 *
 * NOTICE OF LICENSE
 *
 * This source file is released under commercial license by Lamia Oy.
 *
 * @copyright Copyright (c) 2017 Lamia Oy (https://lamia.fi)
 * @author    Szymon Nosal <simon@lamia.fi>
 */

namespace Verifone\Payment\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Payment\Model\Method\AbstractMethod;

class Payment extends AbstractMethod
{
    const CODE = 'verifone_payment';
    const ADDITIONAL_INFO = 'verifone_payment_info';

    /**
     * @var string
     */
    protected $_code = self::CODE;

    /**
     * @var bool
     */
    protected $_isGateway = true;

    /**
     * @var bool
     */
    protected $_canCapture = true;

    /**
     * @var bool
     */
    protected $_canRefund = true;

    /**
     * @var bool
     */
    protected $_canRefundInvoicePartial = true;

    /**
     * @var bool
     */
    protected $_isOffline = false;

    /**
     * @var null
     */
    protected $_minAmount = null;

    /**
     * @var null
     */
    protected $_maxAmount = null;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $_urlBuilder;

    /**
     * @var ClientFactory
     */
    protected $_clientFactory;

    /**
     * @var \Verifone\Payment\Model\Db\Payment\MethodFactory
     */
    protected $_methodFactory;


    /**
     * Verifone constructor.
     *
     * @param \Magento\Framework\Model\Context                   $context
     * @param \Magento\Framework\Registry                        $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory  $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory       $customAttributeFactory
     * @param \Magento\Payment\Helper\Data                       $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Payment\Model\Method\Logger               $logger
     * @param \Magento\Framework\UrlInterface                    $urlBuilder
     * @param array                                              $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\UrlInterface $urlBuilder,
        ClientFactory $clientFactory,
        \Verifone\Payment\Model\Db\Payment\MethodFactory $methodFactory,
        array $data = array()
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            null,
            null,
            $data
        );

        $this->_urlBuilder = $urlBuilder;
        $this->_clientFactory = $clientFactory;
        $this->_methodFactory = $methodFactory;
    }

    /**
     * Refund method for payment
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float                                $amount
     *
     * @return $this
     * @throws LocalizedException
     */
    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {

        /**
         * @var $order \Magento\Sales\Model\Order
         */
        $order = $payment->getOrder();
        /**
         * @var \Verifone\Payment\Model\Client\RestClient $client
         */
        $client = $this->_clientFactory->create('backend');

        $result = $client->orderRefund($order, $payment, $amount);
        if(!$result) {
            throw new LocalizedException(new Phrase('There was a problem while processing refund create request.'));
        }

        return $this;
    }

    /**
     * Determine method availability based on quote amount and config data
     *
     * @param \Magento\Quote\Api\Data\CartInterface|null $quote
     *
     * @return bool
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        if (is_null($quote)) {
            return parent::isAvailable($quote);
        } else {

            /** @var  $model */
            $model = $this->_methodFactory->create();

            return $model->getActiveMethods()->getSize() > 0 && parent::isAvailable($quote);
        }
    }

    /**
     * @return string
     */
    public function getCheckoutRedirectUrl()
    {
        return $this->_urlBuilder->getUrl('verifone_payment/payment/capture');
    }

}