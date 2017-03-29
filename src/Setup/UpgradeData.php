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

namespace Verifone\Payment\Setup;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use Verifone\Payment\Helper\Path;

class UpgradeData implements UpgradeDataInterface
{
    /**
     * @var \Magento\Sales\Model\Order\StatusFactory
     */
    protected $_statusFactory;

    /**
     * @var \Magento\Framework\App\Config\ConfigResource\ConfigInterface
     */
    protected $_resourceConfig;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var \Magento\Customer\Setup\CustomerSetupFactory
     */
    protected $_customerSetupFactory;

    /**
     * @var \Magento\Eav\Model\Entity\Attribute\SetFactory
     */
    private $_attributeSetFactory;

    /**
     * @param \Magento\Sales\Model\Order\StatusFactory $statusFactory
     */
    public function __construct(
        \Magento\Sales\Model\Order\StatusFactory $statusFactory,
        \Magento\Framework\App\Config\ConfigResource\ConfigInterface  $resourceConfig,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Customer\Setup\CustomerSetupFactory $customerSetupFactory,
        \Magento\Eav\Model\Entity\Attribute\SetFactory $attributeSetFactory
    ) {
        $this->_statusFactory = $statusFactory;
        $this->_resourceConfig = $resourceConfig;
        $this->_scopeConfig = $scopeConfig;
        $this->_customerSetupFactory = $customerSetupFactory;
        $this->_attributeSetFactory = $attributeSetFactory;
    }

    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if (!$context->getVersion()) {

        }

        if (version_compare($context->getVersion(), '0.0.2') < 0) {
            $this->upgrade002();
        }

        if (version_compare($context->getVersion(), '0.0.3') < 0) {
            $this->upgrade003($setup);
        }

        if (version_compare($context->getVersion(), '0.0.5') < 0) {
            $this->upgrade005($setup);
        }

        if (version_compare($context->getVersion(), '0.0.6') < 0) {
            $this->upgrade006($setup);
        }

        $setup->endSetup();

    }

    public function upgrade002()
    {
        /** @var \Magento\Sales\Model\Order\Status $status */
        $status = $this->_statusFactory->create();
        $status->setData([
            'status' => 'pending_verifone',
            'label' => 'Pending Verifone'
        ]);
        $status->getResource()->save($status);
        $status->assignState('pending_payment', false, true);
    }

    /**
     * @param ModuleDataSetupInterface $setup
     */
    public function upgrade003(ModuleDataSetupInterface $setup)
    {
        $data = [
            ['code' => 'VerifonePayment', 'name' => 'VerifonePayment - All in one', 'type' => 'ALL', 'active' => '1'],
            ['code' => 'visa', 'name' => 'VISA', 'type' => 'CARD'],
            ['code' => 'master-card', 'name' => 'MASTER_CARD', 'type' => 'CARD'],
            ['code' => 's-pankki-verkkomaksu', 'name' => 'S_PANKKI_VERKKOMAKSU', 'type' => 'BANK'],
            ['code' => 'aktia-maksu', 'name' => 'AKTIA_MAKSU', 'type' => 'BANK'],
            ['code' => 'op-pohjola-verkkomaksu', 'name' => 'OP_POHJOLA_VERKKOMAKSU', 'type' => 'BANK'],
            ['code' => 'nordea-e-payment', 'name' => 'NORDEA_E_PAYMENT', 'type' => 'BANK'],
            ['code' => 'sampo-web-payment', 'name' => 'SAMPO_WEB_PAYMENT', 'type' => 'BANK'],
            ['code' => 'tapiola-verkkomaksu', 'name' => 'TAPIOLA_VERKKOMAKSU', 'type' => 'BANK'],
            ['code' => 'handelsbanken-e-payment', 'name' => 'HANDELSBANKEN_E_PAYMENT', 'type' => 'BANK'],
            ['code' => 'alandsbanken-e-payment', 'name' => 'ALANDSBANKEN_E_PAYMENT', 'type' => 'BANK'],
            ['code' => 'nordea-se-db', 'name' => 'NORDEA_SE_DB', 'type' => 'BANK'],
            ['code' => 'handelsbanken-se-db', 'name' => 'HANDELSBANKEN_SE_DB', 'type' => 'BANK'],
            ['code' => 'swedbank-se-db', 'name' => 'SWEDBANK_SE_DB', 'type' => 'BANK'],
            ['code' => 'seb-se-db', 'name' => 'SEB_SE_DB', 'type' => 'BANK'],
            ['code' => 'invoice-collector', 'name' => 'INVOICE_COLLECTOR', 'type' => 'INVOICE'],
            ['code' => 'bank-axess', 'name' => 'BANK_AXESS', 'type' => 'BANK'],
            ['code' => 'dankort', 'name' => 'DANKORT', 'type' => 'CARD'],
            ['code' => 'nordea-dk-db', 'name' => 'NORDEA_DK_DB', 'type' => 'BANK'],
            ['code' => 'danske-netbetaling', 'name' => 'DANSKE_NETBETALING', 'type' => 'BANK'],
            ['code' => 'handelsbanken-se-invoice', 'name' => 'HANDELSBANKEN_SE_INVOICE', 'type' => 'INVOICE'],
            ['code' => 'amex', 'name' => 'AMEX', 'type' => 'CARD'],
            ['code' => 'diners', 'name' => 'DINERS', 'type' => 'CARD'],
            ['code' => 'handelsbanken-se-account', 'name' => 'HANDELSBANKEN_SE_ACCOUNT', 'type' => 'INVOICE'],
            ['code' => 'svea-webpay-invoice', 'name' => 'SVEA_WEBPAY_INVOICE', 'type' => 'INVOICE'],
            ['code' => 'svea-webpay-installment', 'name' => 'SVEA_WEBPAY_INSTALLMENT', 'type' => 'INVOICE'],
            ['code' => 'saastopankin-verkkomaksu', 'name' => 'SAASTOPANKIN_VERKKOMAKSU', 'type' => 'BANK'],
            ['code' => 'pop-pankin-verkkomaksu', 'name' => 'POP_PANKIN_VERKKOMAKSU', 'type' => 'BANK'],
        ];

        $tableName = $setup->getTable('verifone_payment_methods');

        if ($setup->getConnection()->isTableExists($tableName) == true) {
            foreach ($data as $item) {
                $setup->getConnection()->insert($tableName, $item);
            }
        }
    }

    public function upgrade005($setup)
    {
        $payment = $this->_scopeConfig->getValue(Path::XML_PATH_PAYMENT_METHODS);

        if(empty($payment) || !$this->_isJson($payment)) {
            $this->_resourceConfig->saveConfig(
                Path::XML_PATH_PAYMENT_METHODS,
                'a:1:{s:18:"_1450878527843_843";a:3:{s:8:"position";s:3:"100";s:10:"group_name";s:16:"verifone-default";s:8:"payments";a:1:{i:0;s:15:"VerifonePayment";}}}',
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                0
            );
        }

        $cards = $this->_scopeConfig->getValue(Path::XML_PATH_CARD_METHODS);

        if(empty($cards) || !$this->_isJson($cards)) {
            $this->_resourceConfig->saveConfig(
                Path::XML_PATH_CARD_METHODS,
                'a:1:{s:18:"_1452514030822_822";a:3:{s:8:"position";s:2:"10";s:10:"group_name";s:21:"Verifone Credit Cards";s:8:"payments";a:4:{i:0;s:4:"amex";i:1;s:4:"visa";i:2;s:11:"master-card";i:3;s:6:"diners";}}}',
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                0
            );
        }

    }

    protected function _isJson($string)
    {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    public function upgrade006(ModuleDataSetupInterface $setup)
    {
        /** @var \Magento\Customer\Setup\CustomerSetup $customerSetup */
        $customerSetup = $this->_customerSetupFactory->create(['setup' => $setup]);

        $customerEntity = $customerSetup->getEavConfig()->getEntityType('customer');
        $attributeSetId = $customerEntity->getDefaultAttributeSetId();

        /** @var \Magento\Eav\Model\Entity\Attribute\Set $attributeSet */
        $attributeSet = $this->_attributeSetFactory->create();
        $attributeGroupId = $attributeSet->getDefaultGroupId($attributeSetId);

        $customerSetup->addAttribute(\Magento\Customer\Model\Customer::ENTITY, 'verifone_default_card_id', [
            'type' => 'varchar',
            'label' => 'Default Credit Card saved in Verifone API',
            'input' => 'text',
            'required' => false,
            'visible' => true,
            'user_defined' => false,
            'sort_order' => 1000,
            'position' => 1000,
            'system' => 0,
            'default_value' => ''
        ]);

        //add attribute to attribute set
        $attribute = $customerSetup->getEavConfig()->getAttribute(\Magento\Customer\Model\Customer::ENTITY, 'verifone_default_card_id')
            ->addData([
                'attribute_set_id' => $attributeSetId,
                'attribute_group_id' => $attributeGroupId,
                'used_in_forms' => ['adminhtml_customer'],
            ]);

        $attribute->getResource()->save($attribute);
    }
}