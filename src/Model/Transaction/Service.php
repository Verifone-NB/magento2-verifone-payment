<?php
/**
 *
 * NOTICE OF LICENSE
 *
 * This source file is released under commercial license by Lamia Oy.
 *
 * @copyright  Copyright (c) 2017 Lamia Oy (https://lamia.fi)
 * @author     Szymon Nosal <simon@lamia.fi>
 *
 */

namespace Verifone\Payment\Model\Transaction;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;

class Service
{
    /**
     * @var \Magento\Sales\Api\TransactionRepositoryInterface
     */
    protected $_transactionRepository;

    /**
     * @var \Verifone\Payment\Model\ResourceModel\Transaction
     */
    protected $_transactionResource;

    /**
     * @param \Magento\Sales\Api\TransactionRepositoryInterface $transactionRepository
     * @param \Verifone\Payment\Model\ResourceModel\Transaction      $transactionResource
     */
    public function __construct(
        \Magento\Sales\Api\TransactionRepositoryInterface $transactionRepository,
        \Verifone\Payment\Model\ResourceModel\Transaction $transactionResource
    ) {
        $this->_transactionRepository = $transactionRepository;
        $this->_transactionResource = $transactionResource;
    }

    /**
     * @param string $transactionId
     * @param string $status
     * @param bool   $close
     *
     * @throws LocalizedException
     */
    public function updateStatus($transactionId, $status, $close = false)
    {
        /**
         * @var $transaction \Magento\Sales\Model\Order\Payment\Transaction
         */
        $id = $this->_transactionResource->getIdByTransactionId($transactionId);
        if (!$id) {
            throw new LocalizedException(new Phrase('Transaction ' . $transactionId . ' not found.'));
        }
        $transaction = $this->_transactionRepository->get($id);
        if ($close) {
            $transaction->setIsClosed(1);
        }
        $rawDetailsInfo = $transaction->getAdditionalInformation(\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS);
        $rawDetailsInfo['status'] = $status;
        $transaction
            ->setAdditionalInformation(\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS, $rawDetailsInfo);
        $transaction->getResource()->save($transaction);
    }
}