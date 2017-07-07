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

class Success extends \Magento\Framework\App\Action\Action
{

    public function execute()
    {
        $_request = $this->getRequest();
        $_signedFormData = $_request->getParams();

        $this->_eventManager->dispatch('verifone_paymentinterface_send_saveCreditCard_after', [
            '_class' => get_class($this),
            '_response' => $_signedFormData
        ]);

        $this->messageManager->addSuccessMessage(__('Your card has been successfully added.'));

        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('*/customer_card');
        return $resultRedirect;
    }
}