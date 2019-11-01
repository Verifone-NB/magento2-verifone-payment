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

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\Http;

class Success extends \Magento\Framework\App\Action\Action
{

    public function __construct(Context $context)
    {
        parent::__construct($context);

        // Fix for Magento 2.3 CsrfValidator and backwards-compatibility to prior Magento 2 versions
        if(interface_exists("\Magento\Framework\App\CsrfAwareActionInterface")) {
            $request = $this->getRequest();
            if ($request instanceof Http && $request->isPost()) {
                /** @var \Zend\Http\Headers $headers */
                $headers = $request->getHeaders();
                $headers->addHeaderLine('X_REQUESTED_WITH', 'XMLHttpRequest');
            }
        }
    }

    public function execute()
    {
        $_request = $this->getRequest();
        $_signedFormData = $_request->getParams();

        $this->_eventManager->dispatch('verifone_paymentinterface_send_saveCreditCard_after', [
            '_class' => get_class($this),
            '_response' => $_signedFormData,
            '_success' => true
        ]);

        $this->messageManager->addSuccessMessage(__('Your card has been successfully added.'));

        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('*/customer_card');
        return $resultRedirect;
    }
}