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

namespace Verifone\Payment\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;

class ClientFactory
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->_objectManager = $objectManager;
    }

    /**
     * @param string $type
     *
     * @return \Verifone\Payment\Model\Client\FormClient|\Verifone\Payment\Model\Client\RestClient
     * @throws \Exception
     */
    public function create(string $type)
    {
        switch ($type) {
            case 'form':
            case 'frontend':
                $class = \Verifone\Payment\Model\Client\FormClient::class;
                break;
            case 'rest':
            case 'backend':
                $class = \Verifone\Payment\Model\Client\RestClient::class;
                break;
            default:
                throw new LocalizedException(new Phrase('There was a problem while processing order refund request.'));
        }

        return $this->_objectManager->create($class, []);
    }
}