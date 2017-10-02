<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is released under commercial license by Lamia Oy.
 *
 * @copyright Copyright (c) 2017 Lamia Oy (https://lamia.fi)
 * @author    Szymon Nosal <simon@lamia.fi>
 */


namespace Verifone\Payment\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;

class Fix22Command extends Command
{

    protected function configure()
    {
        $this->setName('verifone:fix22')->setDescription('Fix for upgrade Magento to version 2.2');
    }

    /**
     * It using ObjectManager for backward compatible.
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        //Get Object Manager Instance
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $fieldDataConverter = $objectManager->create(\Magento\Framework\DB\FieldDataConverterFactory::class)->create(\Magento\Framework\DB\DataConverter\SerializedToJson::class);

        $queryModifier =  $objectManager->create(\Magento\Framework\DB\Select\QueryModifierFactory::class)->create(
            'in',
            [
                'values' => [
                    'path' => [
                        'payment/verifone_payment/paymentsgroups_array',
                        'payment/verifone_payment/cardpaymentsgroup_array'
                    ]
                ]
            ]
        );

        $setup = $objectManager->create(\Magento\Framework\Setup\ModuleDataSetupInterface::class);

        $fieldDataConverter->convert(
            $setup->getConnection(),
            $setup->getTable('core_config_data'),
            'config_id',
            'value',
            $queryModifier
        );

        $output->writeln('Fix applied correctly.');
    }
}