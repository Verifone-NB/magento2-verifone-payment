<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is released under commercial license by Lamia Oy.
 *
 * @copyright Copyright (c) 2018 Lamia Oy (https://lamia.fi)
 */


namespace Verifone\Payment\Console\Command;


use Magento\Framework\App\Config\ScopeConfigInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Verifone\Payment\Helper\Path;

class RecreatePaymentsConfigCommand extends Command
{

    protected function configure()
    {
        $this->setName('verifone:recreatePaymentsConfiguration')->setDescription('Fix for upgrade Magento to version 2.2');
    }

    /**
     * It using ObjectManager for backward compatible.
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        //Get Object Manager Instance
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $resourceConfig = $objectManager->create(\Magento\Framework\App\Config\ConfigResource\ConfigInterface::class);
        $productMetadata = $objectManager->create(\Magento\Framework\App\ProductMetadataInterface::class);

        $version = explode('.', $productMetadata->getVersion());

        $dataP['_1450878527843_843'] = [
            'position' => '100',
            'group_name' => 'verifone-default',
            'payments' => ['VerifonePayment']
        ];

        if ($version[1] >= 2) {
            $value = \json_encode($dataP);
        } else {
            $value = \serialize($dataP);
        }

        $resourceConfig->saveConfig(
            Path::XML_PATH_PAYMENT_METHODS,
            $value,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            0
        );

        $dataC['_1452514030822_822'] = [
            'position' => '10',
            'group_name' => 'Verifone Credit Cards',
            'payments' => ['amex', 'visa', 'master-card', 'diners']
        ];

        if ($version[1] >= 2) {
            $value = \json_encode($dataC);
        } else {
            $value = \serialize($dataC);
        }

        $resourceConfig->saveConfig(
            Path::XML_PATH_CARD_METHODS,
            $value,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            0
        );


        $output->writeln('Fix applied correctly.');
    }
}