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


abstract class ErrorAbstract extends \Magento\Framework\App\Action\Action
{
    protected $_message;

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
        $this->messageManager->addErrorMessage(__($this->_message));

        $resultRedirect = $this->resultRedirectFactory->create();

        $resultRedirect->setPath('customer/account');
        return $resultRedirect;

    }
}