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
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;

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

        if(!$context->getVersion()) {

        }

        if (version_compare($context->getVersion(), '0.0.2') < 0) {
            $this->upgrade002();
        }



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
}