<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is released under commercial license by Lamia Oy.
 *
 * @copyright Copyright (c) 2017 Lamia Oy (https://lamia.fi)
 * @author    Szymon Nosal <simon@lamia.fi>
 */


namespace Verifone\Payment\Block\System\Config\Group;

class CardArray extends PaymentArray
{

    protected $_template = 'system/config/field/card.phtml';

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Cms\Model\Wysiwyg\Config $wysiwygConfig,
        \Verifone\Payment\Model\Db\Payment\Method $method,
        \Magento\Framework\Data\Form\Element\Factory $elementFactory,
        array $data = []
    )
    {
        parent::__construct($context, $wysiwygConfig, $method, $elementFactory, $data);
    }

    protected function _renderPayments()
    {
        $_db = $this->_paymentMethod;
        return $_options = $_db->cardsToOptionArray();
    }
}