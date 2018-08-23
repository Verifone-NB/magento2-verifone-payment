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


        if ($version[1] >= 2) {
            $value = '{"_1450878527843_843":{"position":"100","group_name":"verifone-default","payments":["VerifonePayment"]}}';
        } else {
            $value = 'a:1:{s:18:"_1450878527843_843";a:3:{s:8:"position";s:3:"100";s:10:"group_name";s:16:"verifone-default";s:8:"payments";a:1:{i:0;s:15:"VerifonePayment";}}}';
        }

        $resourceConfig->saveConfig(
            Path::XML_PATH_PAYMENT_METHODS,
            $value,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            0
        );

        if ($version[1] >= 2) {
            $value = '{"_1452514030822_822":{"position":"10","group_name":"Verifone Credit Cards","payments":["amex","visa","master-card","diners"]}}';
        } else {
            $value = 'a:1:{s:18:"_1452514030822_822";a:3:{s:8:"position";s:2:"10";s:10:"group_name";s:21:"Verifone Credit Cards";s:8:"payments";a:4:{i:0;s:4:"amex";i:1;s:4:"visa";i:2;s:11:"master-card";i:3;s:6:"diners";}}}';
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