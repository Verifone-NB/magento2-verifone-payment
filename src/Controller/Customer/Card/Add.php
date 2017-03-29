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
use Verifone\Payment\Model\Order\Exception;

class Add extends \Magento\Framework\App\Action\Action
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
        \Magento\Customer\Model\Session $customerSession
    ) {
        parent::__construct($context);

        $this->_scopeConfig = $scopeConfig;
        $this->_clientFactory = $clientFactory;
        $this->_resultPageFactory = $resultPageFactory;
        $this->_logger = $logger;
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

        /** @var \Verifone\Payment\Model\Client\FormClient $client */
        $client = $this->_clientFactory->create('frontend');

        /**
         * @var $resultRedirect \Magento\Framework\Controller\Result\Redirect
         * @var $resultPage     \Magento\Framework\View\Result\Page
         * @var $resultRedirect    \Magento\Framework\Controller\Result\Redirect
         */
        $resultRedirect = $this->resultRedirectFactory->create();
        $redirectUrl = 'customer/account';

        try {

            $cardCreateData = $client->createCardRequest();

            if(is_null($cardCreateData)) {
                $resultRedirect->setPath($redirectUrl, []);
                return $resultRedirect;
            }

//            var_dump($cardCreateData);die();
            $resultPage = $this->_resultPageFactory->create(true,
                ['template' => 'Verifone_Payment::emptyroot.phtml']);

            $resultPage->addHandle($resultPage->getDefaultLayoutHandle());
            $resultPage->getLayout()->getBlock('verifone.card.form')->setCreateData($cardCreateData);

            return $resultPage;

        } catch (Exception $e) {
            $this->_logger->critical($e);
            $redirectUrl = 'customer/account';
            $redirectParams = ['exception' => '1'];
        }

        $resultRedirect->setPath($redirectUrl, $redirectParams);
        return $resultRedirect;

    }

}