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
use Verifone\Core\DependencyInjection\CoreResponse\PaymentResponseImpl;
use Verifone\Payment\Model\Order\Exception;

class Capture extends Action
{

    /**
     * @var \Magento\Checkout\Model\Session\SuccessValidator
     */
    protected $_successValidator;

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $_resultPageFactory;

    /**
     * @var \Verifone\Payment\Model\ClientFactory
     */
    protected $_clientFactory;

    /**
     * @var \Verifone\Payment\Model\Session
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
     * @var \Verifone\Payment\Model\Sales\Order\Config
     */
    protected $_orderConfig;

    /**
     * @var \Verifone\Payment\Helper\Payment
     */
    protected $_paymentHelper;

    /**
     * Capture constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Checkout\Model\Session\SuccessValidator $successValidator
     * @param \Verifone\Payment\Model\Session $session
     * @param \Verifone\Payment\Model\ClientFactory $clientFactory
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Verifone\Payment\Helper\Order $orderHelper
     * @param \Verifone\Payment\Logger\VerifoneLogger $logger
     * @param \Verifone\Payment\Model\Sales\Order\Config $orderConfig
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session\SuccessValidator $successValidator,
        \Verifone\Payment\Model\Session $session,
        \Verifone\Payment\Model\ClientFactory $clientFactory,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Verifone\Payment\Helper\Payment $paymentHelper,
        \Verifone\Payment\Logger\VerifoneLogger $logger,
        \Verifone\Payment\Model\Sales\Order\Config $orderConfig,
        \Verifone\Payment\Helper\Order $orderHelper
    )
    {
        parent::__construct($context);

        $this->_successValidator = $successValidator;
        $this->_session = $session;
        $this->_clientFactory = $clientFactory;
        $this->_resultPageFactory = $resultPageFactory;
        $this->_orderHelper = $orderHelper;
        $this->_logger = $logger;
        $this->_orderConfig = $orderConfig;
        $this->_paymentHelper = $paymentHelper;
    }

    public function execute()
    {
        /**
         * @var $resultRedirect \Magento\Framework\Controller\Result\Redirect
         */
        $resultRedirect = $this->resultRedirectFactory->create();
        $redirectParams = [];
        $redirectUrl = 'verifone_payment/payment/form';

        // Due to PSD/2 regulation payment must be redirect to the payment provider page.
        // Is not possible to make payment request by S2S integration

        $resultRedirect->setPath($redirectUrl, $redirectParams);
        return $resultRedirect;
    }
}