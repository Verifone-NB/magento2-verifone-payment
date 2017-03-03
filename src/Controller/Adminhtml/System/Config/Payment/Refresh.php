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
use Verifone\Payment\Model\Db\Payment\Method;

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
     * @param \Verifone\Payment\Model\Order\PaymentMethod      $paymentMethod
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

        $paymentMethods = $this->_paymentMethodHelper->refreshPaymentMethods(
            $request->getParam('merchant_agreement_code', null),
            $request->getParam('shop_private_keyfile', null),
            $request->getParam('pay_page_public_keyfile', null)
        );

        if (is_string($paymentMethods)) {
            return $resultJson->setData(
                [
                    'valid' => 0,
                    'message' => __('Problem with retrieve payment methods. %1$s' . $paymentMethods),
                ]
            );
        }

        $banks = [];
        $cards = [];

        /** @var \Verifone\Payment\Model\Db\Payment\Method $paymentMethod */
        foreach ($paymentMethods as $paymentMethod) {
            if($paymentMethod->getType() == Method::TYPE_CARD) {
                $cards[] = $paymentMethod->getCode();
            } else {
                $banks[] = $paymentMethod->getCode();
            }
        }

        return $resultJson->setData(
            [
                'valid' => 1,
                'message' => __('Payment methods retrieved correctly. Please save configuration for apply.'),
                'methods' => [
                    'bank' => $banks,
                    'card' => $cards
                ]
            ]
        );

    }
}