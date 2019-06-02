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

namespace Verifone\Payment\Model\Sales\Order;

use Magento\Sales\Model\Order;
use Verifone\Payment\Helper\Path;

class Config extends \Magento\Sales\Model\Order\Config
{

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    public function __construct(
        \Magento\Sales\Model\Order\StatusFactory $orderStatusFactory,
        \Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory $orderStatusCollectionFactory,
        \Magento\Framework\App\State $state,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        parent::__construct(
            $orderStatusFactory,
            $orderStatusCollectionFactory,
            $state
        );
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Gets Verifone-specific default status for state.
     *
     * @param string $state
     *
     * @return string
     */
    public function getStateDefaultStatus($state):?string
    {
        switch ($state) {
            case Order::STATE_PENDING_PAYMENT:
                return $this->scopeConfig->getValue(Path::XML_PATH_ORDER_STATUS_NEW, 'store');
            case Order::STATE_PROCESSING:
                return $this->scopeConfig->getValue(Path::XML_PATH_ORDER_STATUS_PROCESSING, 'store');
        }
        return parent::getStateDefaultStatus($state);
    }
}