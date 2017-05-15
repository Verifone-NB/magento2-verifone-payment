<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is released under commercial license by Lamia Oy.
 *
 * @copyright Copyright (c) 2017 Lamia Oy (https://lamia.fi)
 * @author    Szymon Nosal <simon@lamia.fi>
 */


namespace Verifone\Payment\Controller\Adminhtml\Sales\Order;

use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;
use Verifone\Payment\Model\Client\RestClient;
use Verifone\Payment\Model\Order\Exception;

class CheckStatus extends \Magento\Backend\App\Action
{

    /**
     * @var \Verifone\Payment\Helper\Order
     */
    protected $_order;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Verifone\Payment\Helper\Order $order
    )
    {
        parent::__construct($context);

        $this->_order = $order;
    }

    /**
     * Dispatch request
     *
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function execute()
    {
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setUrl($this->_redirect->getRefererUrl());

        $incrementId = $this->getRequest()->getParam('increment_id', false);

        if (!$incrementId) {
            return $resultRedirect;
        }

        try {

            $transactions = $this->_order->getTransactions($incrementId, true);

            if (!$transactions || empty($transactions)) {
                $this->messageManager->addNoticeMessage(__('No changes in payment for this order'));
            } else {
                $this->messageManager->addSuccessMessage(__('Order status updated'));
            }

        } catch (Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        return $resultRedirect;
    }
}