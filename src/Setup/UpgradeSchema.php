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
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if (!$context->getVersion()) {

        }

        if (version_compare($context->getVersion(), '0.0.3') < 0) {
            $this->upgrade003($setup);
        }

        if (version_compare($context->getVersion(), '0.0.4') < 0) {
            $this->upgrade004($setup);
        }

        if (version_compare($context->getVersion(), '0.0.6') < 0) {
            $this->upgrade006($setup);
        }

        if (version_compare($context->getVersion(), '0.1.2') < 0) {
            $this->upgrade012($setup);
        }

        $setup->endSetup();
    }

    /**
     * @param SchemaSetupInterface $setup
     */
    public function upgrade003(SchemaSetupInterface $setup)
    {
        $table = $setup->getConnection()->newTable(
            $setup->getTable('verifone_payment_methods')
        )->addColumn(
            'method_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'Method Id'
        )->addColumn(
            'code',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => false],
            'Payment Method Code'
        )->addColumn(
            'name',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => false],
            'Payment Method Name'
        )->addColumn(
            'type',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => false],
            'Payment Method Type'
        )->addColumn(
            'active',
            \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
            1,
            ['nullable' => false, 'default' => 0],
            'Payment Method Active'
        )->setComment(
            'Verifone Payment Methods'
        );

        $setup->getConnection()->createTable($table);
    }

    /**
     * @param SchemaSetupInterface $setup
     */
    public function upgrade004(SchemaSetupInterface $setup)
    {
        $setup->getConnection()->addColumn(
            $setup->getTable('sales_order'),
            'payment_method_code',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 50,
                'comment' => 'Payment method'
            ]
        );

        $setup->getConnection()->addColumn(
            $setup->getTable('sales_order_grid'),
            'payment_method_code',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 50,
                'comment' => 'Payment method'
            ]
        );
    }

    public function upgrade006(SchemaSetupInterface $setup)
    {
        $tableName = $setup->getTable('verifone_payment_saved');

        $table = $setup->getConnection()->newTable(
            $tableName
        )->addColumn(
            'entity_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'Id'
        )->addColumn(
            'customer_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['unsigned' => true],
            'Customer Id'
        )->addColumn(
            'order_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true],
            'Order Id'
        )->addColumn(
            'serialized_data',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            null,
            ['nullable' => false],
            'Serialized Verifone Request Data (address)'
        )->addColumn(
            'active',
            \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
            null,
            ['nullable' => true, 'default' => 0],
            'Is Active'
        )->addColumn(
            'gate_method_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true, 'default' => null],
            'Gate Method'
        )->addColumn(
            'save_method',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => true, 'default' => null],
            'Save Method'
        )->addIndex(
            $setup->getIdxName('verifone_payment_saved', ['customer_id']),
            ['customer_id']
        )->addForeignKey(
            $setup->getFkName(
                'verifone_payment_saved',
                'customer_id',
                'customer_entity',
                'entity_id'
            ),
            'customer_id',
            $setup->getTable('customer_entity'),
            'entity_id',
            \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
        )->setComment(
            'Verifone Payment Saved'
        );

        $setup->getConnection()->createTable($table);

        // Create card number
        $setup->getConnection()->addColumn(
            $setup->getTable('sales_order'),
            'masked_pan_number',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 50,
                'comment' => 'Masked Pan Number'
            ]
        );
    }

    public function upgrade012(SchemaSetupInterface $setup)
    {
        $tableName = $setup->getTable('verifone_order_process_status');

        $table = $setup->getConnection()->newTable($tableName)->addColumn(
            'order_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            20,
            ['nullable' => false, 'primary' => true],
            'Order Increment Id'
        )->addColumn(
            'under_process',
            \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
            null,
            ['nullable' => false,],
            'Order process status'
        );

        $setup->getConnection()->createTable($table);
    }
}