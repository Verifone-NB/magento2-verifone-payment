<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is released under commercial license by Lamia Oy.
 *
 * @copyright Copyright (c) 2017 Lamia Oy (https://lamia.fi)
 * @author    Szymon Nosal <simon@lamia.fi>
 */


namespace Verifone\Payment\Controller\Customer\Card;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\App\Action\Context;
use Verifone\Payment\Helper\Path;

class SaveDefault extends \Magento\Framework\App\Action\Action
{

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $_customerRepository;

    /**
     * Index constructor.
     * @param Context $context
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Customer\Model\Session $customerSession,
        CustomerRepositoryInterface $customerRepository
    ) {
        parent::__construct($context);

        $this->_scopeConfig = $scopeConfig;
        $this->_customerSession = $customerSession;
        $this->_customerRepository = $customerRepository;
    }

    /**
     * Dispatch request
     *
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function execute()
    {
        if(!$this->_scopeConfig->getValue(Path::XML_PATH_ALLOW_TO_SAVE_CC) || !$this->_customerSession->isLoggedIn() || !$this->_customerSession->getCustomerId()) {
            $this->_redirect('customer/account');
        }

        $id = $this->getRequest()->getParam('_id');

        if(!$id) {
            $this->messageManager->addErrorMessage(__('Card has not been set as default.'));
            return $this->_redirect('*/*');
        }

        $customer = $this->_customerRepository->getById($this->_customerSession->getCustomerId());
        $customer->setCustomAttribute('verifone_default_card_id', $id);

        if($this->_customerRepository->save($customer)) {
            $this->messageManager->addSuccessMessage(__('Card has been set as default.'));
        } else {
            $this->messageManager->addErrorMessage(__('Card has not been set as default.'));
        }

        return $this->_redirect('*/*');
    }
}