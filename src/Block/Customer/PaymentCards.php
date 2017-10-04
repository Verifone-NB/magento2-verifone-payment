<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is released under commercial license by Lamia Oy.
 *
 * @copyright Copyright (c) 2017 Lamia Oy (https://lamia.fi)
 * @author    Szymon Nosal <simon@lamia.fi>
 */


namespace Verifone\Payment\Block\Customer;

use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Verifone\Payment\Helper\Path;

class PaymentCards extends \Magento\Customer\Block\Account\Dashboard
{
    protected $_template = 'verifone_payment/customer/paymentcards.phtml';

    /**
     * @var \Verifone\Payment\Helper\Saved
     */
    protected $_saved;

    /**
     * @var \Verifone\Payment\Model\Db\Payment\Method
     */
    protected $_method;

    /**
     * @var \Magento\Framework\App\ProductMetadataInterface
     */
    protected $_productMetadata;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory,
        CustomerRepositoryInterface $customerRepository,
        AccountManagementInterface $customerAccountManagement,
        \Verifone\Payment\Helper\Saved $saved,
        \Verifone\Payment\Model\Db\Payment\Method $method,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        array $data = [])
    {
        parent::__construct($context, $customerSession, $subscriberFactory, $customerRepository, $customerAccountManagement, $data);

        $this->_saved = $saved;
        $this->_method = $method;
        $this->_productMetadata = $productMetadata;
    }

    public function getAcceptedCards()
    {
        $string = $this->_scopeConfig->getValue(Path::XML_PATH_CARD_METHODS);
        $version = explode('.', $this->_productMetadata->getVersion());

        if($version[1] >= 2) {
            $_cardsPaymentsGroupConfig = json_decode($string, true);
        } else {
            $_cardsPaymentsGroupConfig = unserialize($string);
        }

        $_cardsPaymentsGroup = reset($_cardsPaymentsGroupConfig);
        if (!isset($_cardsPaymentsGroup['payments'])) {
            return array();
        }

        $_cardsPayments = $_cardsPaymentsGroup['payments'];

        $_dbCardsPayments = $this->_method->cardsToOptionArray();

        $_result = array();
        foreach ($_dbCardsPayments as $_dbCardsPayment) {
            $_result[] = in_array($_dbCardsPayment['value'], $_cardsPayments) ? $_dbCardsPayment['label'] : '';
        }

        return array_filter($_result);
    }

    public function getSavedPayments()
    {
        return $this->_saved->getSavedPayments(true);
    }

    public function getDateFromVerifoneStr($_validityDate)
    {

        $_date = \DateTime::createFromFormat('mY', str_pad($_validityDate, 6, '0', STR_PAD_LEFT));

        return $_date->format('m/Y');

    }

    public function formatTitle($_title)
    {
        return 'XXXX-XXXX-XXXX-' . substr($_title, -4);
    }
}