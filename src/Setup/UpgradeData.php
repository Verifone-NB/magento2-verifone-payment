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

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;

class UpgradeData implements UpgradeDataInterface
{
    /**
     * @var \Magento\Sales\Model\Order\StatusFactory
     */
    protected $statusFactory;

    /**
     * @param \Magento\Sales\Model\Order\StatusFactory $statusFactory
     */
    public function __construct(
        \Magento\Sales\Model\Order\StatusFactory $statusFactory
    ) {
        $this->statusFactory = $statusFactory;
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

        $setup->endSetup();

    }

    public function upgrade002()
    {
        /** @var \Magento\Sales\Model\Order\Status $status */
        $status = $this->statusFactory->create();
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
}