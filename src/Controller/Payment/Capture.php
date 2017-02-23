<?php
/**
 *
 * NOTICE OF LICENSE
 *
 * This source file is released under commercial license by Lamia Oy.
 *
 * @copyright Copyright (c) 2017 Lamia Oy (https://lamia.fi)
 * @author    Szymon Nosal <simon@lamia.fi>
 */

namespace Verifone\Payment\Controller\Payment;

use Magento\Framework\App\Action\Action;

class Capture extends Action
{

    /**
     * @var \Magento\Checkout\Model\Session\SuccessValidator
     */
    protected $_successValidator;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_session;

    /**
     * @var \Verifone\Payment\Helper\Order
     */
    protected $_orderHelper;

    /**
     * @var \Verifone\Payment\Logger\VerifoneLogger
     */
    protected $_logger;

    /**
     * @var \Verifone\Payment\Model\Client
     */
    protected $_client;
    /**
     * @param \Magento\Framework\App\Action\Context $context
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session\SuccessValidator $successValidator,
        \Magento\Checkout\Model\Session $session,
        \Verifone\Payment\Helper\Order $orderHelper,
        \Verifone\Payment\Logger\VerifoneLogger $logger,
        \Verifone\Payment\Model\Client $client
    ) {
        parent::__construct($context);

        $this->_successValidator = $successValidator;
        $this->_session = $session;
        $this->_orderHelper = $orderHelper;
        $this->_logger = $logger;
        $this->_client = $client;
    }

    /**
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        /**
         * @var $resultRedirect \Magento\Framework\Controller\Result\Redirect
         */
        $resultRedirect = $this->resultRedirectFactory->create();

        if($this->_successValidator->isValid()) {
            $order = $this->_session->getLastRealOrder();
            $redirectUrl = 'verifone_payment/payment/form';
            $redirectParams = [];
        } else {
            $redirectUrl = 'checkout/cart';
            $redirectParams = [];
        }

        $resultRedirect->setPath($redirectUrl, $redirectParams);
        return $resultRedirect;
    }
}