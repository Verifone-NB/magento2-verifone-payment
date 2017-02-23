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


namespace Verifone\Payment\Controller\Adminhtml\System\Config\Payment;

use Magento\Framework\App\ResponseInterface;

/**
 * Class Refresh
 *
 * @package Verifone\Payment\Controller\Adminhtml\System\Config\Payment
 */
class Refresh extends \Magento\Backend\App\Action
{

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var \Verifone\Payment\Model\Order\PaymentMethod
     */
    protected $_paymentMethodHelper;

    /**
     * Refresh constructor.
     *
     * @param \Magento\Backend\App\Action\Context              $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Verifone\Payment\Model\Order\PaymentMethod            $paytypeHelper
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Verifone\Payment\Model\Order\PaymentMethod $paymentMethod
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->_paymentMethodHelper = $paymentMethod;
    }

    /**
     * Dispatch request
     *
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function execute()
    {

        /**
         * @var \Magento\Framework\Controller\Result\Json $resultJson
         */
        $resultJson = $this->resultJsonFactory->create();

        $request = $this->getRequest();

        $paytypes = $this->_paymentMethodHelper->refreshPaymentMethods(
            $request->getParam('merchant_agreement_code', null),
            $request->getParam('shop_private_keyfile', null),
            $request->getParam('pay_page_public_keyfile', null)
        );

        if (!$paytypes) {
            return $resultJson->setData(
                [
                    'valid' => 0,
                    'message' => 'Problem with retrieve payment methods',
                ]
            );
        }

        return $resultJson->setData(
            [
                'valid' => 1,
                'message' => 'Payment methods retrieved correctly',
                'methods' => $paytypes
            ]
        );

    }
}