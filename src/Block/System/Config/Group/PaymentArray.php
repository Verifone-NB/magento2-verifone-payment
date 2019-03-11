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

use Verifone\Payment\Helper\Path;

class PaymentArray extends \Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray
{
    /**
     * Template path
     *
     * @var string
     */
    protected $_template = 'system/config/field/array.phtml';

    protected $_magentoAttributes;
    protected $_editOrder;

    /** @var \Magento\Cms\Model\Wysiwyg\Config  */
    protected $_wysiwygConfig;

    /**
     * @var \Verifone\Payment\Model\Db\Payment\Method
     */
    protected $_paymentMethod;

    /**
     * @var \Magento\Framework\Data\Form\Element\Factory
     */
    protected $_elementFactory;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Cms\Model\Wysiwyg\Config $wysiwygConfig,
        \Verifone\Payment\Model\Db\Payment\Method $method,
        \Magento\Framework\Data\Form\Element\Factory $elementFactory,
        array $data = [])
    {
        $this->_wysiwygConfig = $wysiwygConfig;
        $this->_paymentMethod = $method;
        $this->_elementFactory = $elementFactory;

        $this->addColumn('position', array(
            'label' => __('Group Position'),
            'size' => 2,
            'style' => 'width: auto'
        ));

        $this->addColumn('group_name', array(
            'label' => __('Group name'),
            'size' => 20,
            'style' => 'width: 275px',
        ));

        $this->addColumn('payments', array(
            'label' => __('Payments'),
            'size' => 10,
            'style' => 'width: auto'
        ));

        $this->_addAfter = false;
        $this->_editOrder = true;

        $this->_addButtonLabel = __('Add new group');

        parent::__construct($context, $data);
    }

    public function getDefaultGroupName()
    {
        return $this->_scopeConfig->getValue(Path::XML_PATH_PAYMENT_DEFAULT_GROUP);
    }

    public function renderCellTemplate($columnName)
    {
        if (empty($this->_columns[$columnName])) {
            throw new \Exception('Wrong column name specified.');
        }
        $column = $this->_columns[$columnName];
        $inputName = $this->getElement()->getName() . '[#{_id}][' . $columnName . ']';
        if ($columnName == 'payments') {
            $payments = $this->_renderPayments();

            if($payments !== null && $column['size'] > \count($payments)) {
                $column['size'] = \count($payments);
            }
            $column['size'] = $column['size'] > count($payments) ? count($payments) : $column['size'];

            $rendered = '<select style="width: 200px;" name="' . $inputName . '[]" id="select_' . $columnName . '#{_id}" size="' . $column['size'] . '" multiple="multiple">';

            if(!is_null($payments)) {
                foreach ($payments as $_option) {
                    $rendered .= '<option value="' . $_option['value'] . '">' . $_option['label'] . '</option>';
                }
            }
            $rendered .= '</select>';
            return $rendered;
        } elseif ($columnName == 'group_name') {
            $rendered = '<input type="text" name="' . $inputName . '" value="#{' . $columnName . '}" ' .
                'id="textfield_' . $columnName . '#{_id}"' .
                ($column['size'] ? 'size="' . $column['size'] . '"' : '') . ' class="' .
                (isset($column['class']) ? $column['class'] : 'input-text') . '"' .
                (isset($column['style']) ? ' style="' . $column['style'] . '"' : '') . '/>';

            $rendered .= '<input type="checkbox" name="' . $inputName . '" id="checkbox_' . $columnName . '#{_id}" value="' . $this->_scopeConfig->getValue(Path::XML_PATH_PAYMENT_DEFAULT_GROUP) . '" />';
            $rendered .= '<label for="checkbox_' . $columnName . '#{_id}"">' . __('Show those without groups') . '</label>';

            return $rendered;
        }

        if ($column['renderer']) {
            return $column['renderer']->setInputName($inputName)->setColumnName($columnName)->setColumn($column)
                ->toHtml();
        }
        return '<input type="text" name="' . $inputName . '" value="#{' . $columnName . '}" ' .
            ($column['size'] ? 'size="' . $column['size'] . '"' : '') . ' class="' .
            (isset($column['class']) ? $column['class'] : 'input-text') . '"' .
            (isset($column['style']) ? ' style="' . $column['style'] . '"' : '') . '/>';
    }

    protected function _renderPayments()
    {
        $_db = $this->_paymentMethod;
        return $_options = $_db->toOptionArray();
    }

    public function getEditOrder()
    {
        return $this->_editOrder;
    }

    public function getAddAfter()
    {
        return $this->_addAfter;
    }

    public function getAddButtonLabel()
    {
        return $this->_addButtonLabel;
    }

}