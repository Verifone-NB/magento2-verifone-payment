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


use Verifone\Core\Configuration\FieldConfigImpl;
use Verifone\Core\DependencyInjection\Service\PaymentInfoImpl;
use Verifone\Payment\Helper\Path;

class SaveCreditCard implements \Magento\Framework\Event\ObserverInterface
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

    /**
     * @var \Verifone\Payment\Helper\Saved
     */
    protected $_saved;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Verifone\Payment\Helper\Order $order,
        \Verifone\Payment\Model\ClientFactory $clientFactory,
        \Verifone\Payment\Helper\Saved $saved
    )
    {
        $this->_scopeConfig = $scopeConfig;
        $this->_order = $order;
        $this->_clientFactory = $clientFactory;
        $this->_saved = $saved;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $_requestData = $observer->getEvent()->getData('_requestData');

        if (!$this->_scopeConfig->getValue(Path::XML_PATH_ALLOW_TO_SAVE_CC) ||
            !isset($_requestData[FieldConfigImpl::PAYMENT_SAVE_METHOD]) ||
            !in_array($_requestData[FieldConfigImpl::PAYMENT_SAVE_METHOD], [
                PaymentInfoImpl::SAVE_METHOD_AUTO_SAVE,
                PaymentInfoImpl::SAVE_METHOD_SAVE_ONLY
            ])
        ) {
            return $this;
        }

        $this->_saved->savePaymentMethod($observer->getEvent()->getData('_requestData'));

        return $this;
    }

}