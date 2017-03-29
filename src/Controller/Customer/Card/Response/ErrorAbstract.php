<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is released under commercial license by Lamia Oy.
 *
 * @copyright Copyright (c) 2017 Lamia Oy (https://lamia.fi)
 * @author    Szymon Nosal <simon@lamia.fi>
 */


namespace Verifone\Payment\Controller\Customer\Card\Response;


abstract class ErrorAbstract extends \Magento\Framework\App\Action\Action
{
    protected $_message;

    public function execute()
    {
        $this->messageManager->addErrorMessage(__($this->_message));

        $resultRedirect = $this->resultRedirectFactory->create();

        $resultRedirect->setPath('customer/account');
        return $resultRedirect;

    }
}