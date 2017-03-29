<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is released under commercial license by Lamia Oy.
 *
 * @copyright Copyright (c) 2017 Lamia Oy (https://lamia.fi)
 * @author    Szymon Nosal <simon@lamia.fi>
 */

namespace Verifone\Payment\Helper;

use Verifone\Core\Configuration\FieldConfigImpl;
use Verifone\Core\DependencyInjection\CoreResponse\CardImpl;

use \Verifone\Payment\Model\Db\Payment\Saved as SavedCC;

class Saved
{

    /**
     * @var \Verifone\Payment\Model\Db\Payment\SavedFactory
     */
    protected $_savedFactory;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var \Verifone\Payment\Model\ClientFactory
     */
    protected $_clientFactory;

    /**
     * @var \Magento\Customer\Model\Customer
     */
    protected $_customer = null;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $_customerRepository;

    protected $_cards;

    protected static $_proceededCardRequests = [];
    const SAVE_METHOD_ORDER = 'order';
    const SAVE_METHOD_ADDNEWCARD = 'addNewCard';

    public function __construct(
        \Verifone\Payment\Model\Db\Payment\SavedFactory $savedFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Verifone\Payment\Model\ClientFactory $clientFactory
    )
    {
        $this->_savedFactory = $savedFactory;
        $this->_customerSession = $customerSession;
        $this->_customerRepository = $customerRepository;
        $this->_clientFactory = $clientFactory;

    }

    public function savePaymentMethod($_requestData)
    {
        $session = $this->_customerSession;

        if (!$session->getId()) {
            return false;
        }

        if ($_customer = $this->_getCustomer()) {

            $_savedMethod = $this->_savedFactory->create();
            $_data = $this->_filterRequestData($_requestData);

            $_savedMethod->setData('customer_id', $_customer->getId());
            $_savedMethod->setData('order_id', $_requestData[FieldConfigImpl::ORDER_NOTE]);
            $_savedMethod->setData('save_method', self::SAVE_METHOD_ADDNEWCARD);
            $_savedMethod->setData('active', '0');


            if (isset($_requestData[FieldConfigImpl::ORDER_NUMBER]) && $_requestData[FieldConfigImpl::ORDER_NUMBER] != 'addNewCard') {
                $_savedMethod->setData('customer_id', $_customer->getId());
                $_savedMethod->setData('order_id', $_requestData[FieldConfigImpl::ORDER_NUMBER]);
                $_savedMethod->setData('save_method', self::SAVE_METHOD_ORDER);
                $_savedMethod->setData('active', '0');
            }

            $_savedMethod->setData('serialized_data', serialize($_data));

            return $_savedMethod->getResource()->save($_savedMethod);
        }

        $this->_saveCardsInSession();

        return false;
    }

    public function updatePaymentMethods()
    {
        $requestsToCheck = $this->getNotActivatedMethodsForCustomer();
        if (count($requestsToCheck) > 0) {
            /** @var SavedCC $request */
            foreach ($requestsToCheck as $request) {
                if (!in_array($request->getId(), self::$_proceededCardRequests)) {
                    self::$_proceededCardRequests[] = $request->getId();
                    foreach ($this->getCardDetails($request) as $_key => $_savedCard) {
                        $_cardObj = $this->getCardByGateMethodId($_key);

                        $_cardObj
                            ->setSerializedData($request->getSerializedData())
                            ->setCustomerId($this->_getCustomer()->getId())
                            ->setActive(1)
                            ->setGateMethodId($_key);
                        $_cardObj->getResource()->save($_cardObj);

                        unset($_cardObj);
                    }
                } else {
                    return true;
                }
            }
            return $this->savedCleanUp();
        }

        return true;
    }

    public function getSavedMethodsForCustomer($_activeOnly = false)
    {
        $_collection = $this->_savedFactory->create()->getCollection()
            ->addFieldToFilter('customer_id', array('eq' => $this->_getCustomer()->getId()));

        if ($_activeOnly) {
            $_collection->addFieldToFilter('active', array('eq' => '1'));
        }

        return $_collection;
    }

    public function getSavedMethods($_requestData)
    {
        $_collection = $this->getSavedMethodsForCustomer();

        if (
            isset($_requestData[FieldConfigImpl::ORDER_NUMBER])
            && isset($_requestData[FieldConfigImpl::ORDER_NOTE])
            && $_requestData[FieldConfigImpl::ORDER_NUMBER] == 'addNewCard'
        ) {

            $_collection->addFieldToFilter('order_id', array('eq' => $_requestData[FieldConfigImpl::ORDER_NOTE]));
        }

        if (
            isset($_requestData[FieldConfigImpl::ORDER_NUMBER])
            && $_requestData[FieldConfigImpl::ORDER_NUMBER] != 'addNewCard'
        ) {

            $_collection->addFieldToFilter('order_id', array('eq' => $_requestData[FieldConfigImpl::ORDER_NUMBER]));
        }

        return $_collection->count() ? $_collection->getFirstItem() : $this;
    }

    public function savedCleanUp()
    {
        $_collection = $this->getNotActivatedMethodsForCustomer();

        /** @var SavedCC $_inactiveCard */
        foreach ($_collection as $_inactiveCard) {
            if ($_inactiveCard->getId()) {
                $_inactiveCard->getResource()->delete($_inactiveCard);
            }
        }

        return $this;
    }

    public function getNotActivatedMethodsForCustomer()
    {
        $_collection = $this->_savedFactory->create()->getCollection()
            ->addFieldToFilter('customer_id', array('eq' => $this->_getCustomer()->getId()))
            ->addFieldToFilter('active', 0);

        return $_collection;
    }

    /**
     * @param $_id
     * @return \Magento\Framework\DataObject|null
     */
    public function getCardById($_id)
    {
        if (!$_id) {
            return null;
        }

        $_collection = $this->getSavedMethodsForCustomer(true);

        $_collection->addFieldToFilter('entity_id', array('eq' => $_id));

        /** @var SavedCC $_card */
        $_card = $_collection->getFirstItem();

        return $_card;
    }

    public function getCardByOrderId($_orderId)
    {
        if (!$_orderId) {
            return null;
        }

        $_collection = $this->getSavedMethodsForCustomer();
        $_collection->addFieldToFilter('order_id', array('eq' => $_orderId));

        /** @var SavedCC $_card */
        $_card = $_collection->getFirstItem();

        return $this->_savedFactory->create()->load($_card->getId());
    }

    public function getCardByGateMethodId($_gateMethodId)
    {
        $_collection = $this->getSavedMethodsForCustomer();

        $_collection->addFieldToFilter('gate_method_id', array('eq' => $_gateMethodId));

        $_card = $_collection->getFirstItem();

        if (!$_card) {
            $_card = $this->_savedFactory->create();
        }

        return $_card;
    }

    public function removeByGateMethodId($_gateMethodId)
    {
        /** @var SavedCC $_cardToRemove */
        $_cardToRemove = $this->getCardByGateMethodId($_gateMethodId);

        if ($_cardToRemove->getResource()->load($_cardToRemove, $_cardToRemove->getId())) {
            return $_cardToRemove->getResource()->delete($_cardToRemove);
        }

        return null;
    }

    /**
     * @return \Magento\Customer\Model\Customer|null
     */
    protected function _getCustomer()
    {
        if (is_null($this->_customer)) {
            $session = $this->_customerSession;
            $_customer = $session->getCustomer();

            $this->_customer = $_customer ? $_customer : null;
        }

        return $this->_customer;
    }

    /**
     * @param \Verifone\Payment\Model\Db\Payment\Saved $card
     * @return mixed
     */
    public function getCardDetails($card)
    {
        $_additionalData = unserialize($card->getData('serialized_data'));

        /** @var \Verifone\Payment\Model\Client\RestClient $client */
        $client = $this->_clientFactory->create('backend');

        $_savedPaymentMethodsResponse = $client->getListSavedPaymentMethods();

        if(is_null($_savedPaymentMethodsResponse)) {
            return array();
        }

        $_formattedSavedPaymentMethods = $this->_formatSavedPaymentMethods($_savedPaymentMethodsResponse->getBody());

        if (empty($_formattedSavedPaymentMethods)) {

            /** @var \Verifone\Payment\Model\Db\Payment\Saved $cc */
            $cc = $card->getResource()->load($cc, $card->getId());
            $cc->getResource()->delete($cc);
        }

        return $_formattedSavedPaymentMethods;

    }

    protected function _filterRequestData($_data)
    {
        $_result = array();

//        $_result[FieldConfigImpl::ORDER_NUMBER] = isset($_data[FieldConfigImpl::ORDER_NUMBER]) ? $_data[FieldConfigImpl::ORDER_NUMBER] : '';
        $_result[FieldConfigImpl::CUSTOMER_FIRST_NAME] = isset($_data[FieldConfigImpl::CUSTOMER_FIRST_NAME]) ? $_data[FieldConfigImpl::CUSTOMER_FIRST_NAME] : '';
        $_result[FieldConfigImpl::CUSTOMER_LAST_NAME] = isset($_data[FieldConfigImpl::CUSTOMER_LAST_NAME]) ? $_data[FieldConfigImpl::CUSTOMER_LAST_NAME] : '';
        $_result[FieldConfigImpl::CUSTOMER_EMAIL] = isset($_data[FieldConfigImpl::CUSTOMER_EMAIL]) ? $_data[FieldConfigImpl::CUSTOMER_EMAIL] : '';
        $_result[FieldConfigImpl::CUSTOMER_PHONE_NUMBER] = isset($_data[FieldConfigImpl::CUSTOMER_PHONE_NUMBER]) ? $_data[FieldConfigImpl::CUSTOMER_PHONE_NUMBER] : '';
        $_result[FieldConfigImpl::CUSTOMER_EXTERNAL_ID] = isset($_data[FieldConfigImpl::CUSTOMER_EXTERNAL_ID]) ? $_data[FieldConfigImpl::CUSTOMER_EXTERNAL_ID] : '';
        $_result[FieldConfigImpl::CUSTOMER_ADDRESS_LINE_1] = isset($_data[FieldConfigImpl::CUSTOMER_ADDRESS_LINE_1]) ? $_data[FieldConfigImpl::CUSTOMER_ADDRESS_LINE_1] : '';
        $_result[FieldConfigImpl::CUSTOMER_ADDRESS_LINE_2] = isset($_data[FieldConfigImpl::CUSTOMER_ADDRESS_LINE_2]) ? $_data[FieldConfigImpl::CUSTOMER_ADDRESS_LINE_2] : '';
        $_result[FieldConfigImpl::CUSTOMER_ADDRESS_LINE_3] = isset($_data[FieldConfigImpl::CUSTOMER_ADDRESS_LINE_3]) ? $_data[FieldConfigImpl::CUSTOMER_ADDRESS_LINE_3] : '';
        $_result[FieldConfigImpl::CUSTOMER_ADDRESS_CITY] = isset($_data[FieldConfigImpl::CUSTOMER_ADDRESS_CITY]) ? $_data[FieldConfigImpl::CUSTOMER_ADDRESS_CITY] : '';
        $_result[FieldConfigImpl::CUSTOMER_ADDRESS_POSTAL] = isset($_data[FieldConfigImpl::CUSTOMER_ADDRESS_POSTAL]) ? $_data[FieldConfigImpl::CUSTOMER_ADDRESS_POSTAL] : '';
        $_result[FieldConfigImpl::CUSTOMER_ADDRESS_COUNTRY] = isset($_data[FieldConfigImpl::CUSTOMER_ADDRESS_COUNTRY]) ? $_data[FieldConfigImpl::CUSTOMER_ADDRESS_COUNTRY] : '';
        return array_filter($_result, 'strlen');
    }

    protected function _saveCardsInSession($_cards = null)
    {
        $_session = $this->_customerSession;
        $_session->setVerifoneCards($_cards);

        return $_cards;
    }

    protected function _getCardsFromSession()
    {
        $_session = $this->_customerSession;
        return $_session->getVerifoneCards();
    }

    protected function _formatSavedPaymentMethods($_response = array())
    {
        if (empty($_response)) {
            return array();
        }

        $_result = array();

        /**
         * @var  $_key
         * @var CardImpl $_card
         */
        foreach ($_response as $_key => $_card) {

            if ($_card->getId()) {
                $_result[$_key]['card-method-id'] = $_card->getId();
            }

            if ($_card->getCode()) {
                $_result[$_key]['card-method-code'] = $_card->getCode();
            }

            if ($_card->getTitle()) {
                $_result[$_key]['card-method-title'] = $_card->getTitle();
            }

            if ($_card->getValidity()) {
                $_result[$_key]['card-expected-validity'] = $_card->getValidity();
            }
        }

        return $this->_formatSavedPaymentsResultArray($_result);

    }

    protected function _formatSavedPaymentsResultArray($_savedPaymentsArray = array())
    {
        if (empty($_savedPaymentsArray)) {
            return array();
        }

        $_result = array();
        foreach ($_savedPaymentsArray as $_savedPayment) {
            $_result[$_savedPayment['card-method-id']] = $_savedPayment;
        }

        return $_result;
    }

    public function getSavedPayments($_forceRequest = false)
    {
        if (!is_null($this->_getCardsFromSession()) && !$_forceRequest) {
            return $this->_getCardsFromSession();
        }

        $_savedCardsCollection = $this->getSavedMethodsForCustomer(true);

        $_result = array();

        foreach ($_savedCardsCollection as $_card) {
            foreach ($this->getCardDetails($_card) as $_key => $_savedCard) {
                $_result[$_key] = $_savedCard;
                $_result[$_key]['is_default'] = $this->_isVerifoneDefaultCardId($_key);

                if (!$this->getCardByGateMethodId($_key)->getId()) {

                    $_card
                        ->setId(null)
                        ->setCustomerId($this->_getCustomer()->getId())
                        ->setActive(1)
                        ->setGateMethodId($_key)
                        ->save();
                }
            }
        }

        return $this->_saveCardsInSession($_result);
    }

    protected function _isVerifoneDefaultCardId($_id)
    {
        $_customer = $this->_getCustomer();
        $_defaultCardId = $_customer->getData('verifone_default_card_id');

        return (boolean)($_defaultCardId == $_id);
    }


}