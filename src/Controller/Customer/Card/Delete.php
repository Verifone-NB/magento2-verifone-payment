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

use Magento\Framework\App\ResponseInterface;
use Verifone\Payment\Helper\Path;

class Delete extends \Magento\Framework\App\Action\Action
{

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var \Verifone\Payment\Model\ClientFactory
     */
    protected $_clientFactory;

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $_resultPageFactory;

    /**
     * @var \Verifone\Payment\Logger\VerifoneLogger
     */
    protected $_logger;

    /**
     * @var \Verifone\Payment\Helper\Saved
     */
    protected $_saved;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * Index constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Verifone\Payment\Model\ClientFactory $clientFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Verifone\Payment\Logger\VerifoneLogger $logger,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Verifone\Payment\Model\ClientFactory $clientFactory,
        \Verifone\Payment\Helper\Saved $saved,
        \Magento\Customer\Model\Session $customerSession
    ) {
        parent::__construct($context);

        $this->_scopeConfig = $scopeConfig;
        $this->_clientFactory = $clientFactory;
        $this->_resultPageFactory = $resultPageFactory;
        $this->_logger = $logger;
        $this->_saved = $saved;
        $this->_customerSession = $customerSession;

    }

    /**
     * Dispatch request
     *
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function execute()
    {
        if(!$this->_scopeConfig->getValue(Path::XML_PATH_ALLOW_TO_SAVE_CC)  || !$this->_customerSession->isLoggedIn()) {
            $this->_redirect('customer/account');
        }

        $_id = $this->getRequest()->getParam('_id');

        /** @var \Verifone\Payment\Model\Client\RestClient $client */
        $client = $this->_clientFactory->create('backend');

        $_savedPaymentMethodsResponse = $client->removeSavedPaymentMethod($_id)->getBody();

        if($_savedPaymentMethodsResponse) {

            $this->_saved->removeByGateMethodId($_id);
            $this->messageManager->addSuccessMessage(__('Card has been successfully removed.'));
        } else {
            $this->messageManager->addErrorMessage(__('Server error, please try again later.'));
        }

        $resultRedirect = $this->resultRedirectFactory->create();

        $resultRedirect->setPath('*/*');
        return $resultRedirect;
    }
}