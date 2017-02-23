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

namespace Verifone\Payment\Test\Unit\Model;

use Verifone\Payment\Model\Payment;

class PaymentTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Verifone\Payment\Model\Payment
     */
    protected $_model;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $_scopeConfig;

    protected function setUp()
    {
        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->_scopeConfig = $this->getMock('Magento\Framework\App\Config\ScopeConfigInterface', [], [], '', false);
        $this->_model = $objectManagerHelper->getObject(
            Payment::class,
            [
                'scopeConfig' => $this->_scopeConfig
            ]
        );

    }

    /**
     * @group Verifone_Payment
     */
    public function testIsAvailableNoQuote()
    {
        $this->assertFalse($this->_model->isAvailable());
    }

    /**
     * @group Verifone_Payment
     */
    public function testIsAvailableNotActive()
    {
        $this->_scopeConfig->expects($this->at(0))->method('getValue')->willReturn(0);
        $this->assertFalse($this->_model->isAvailable($this->_getQuoteMock()));
    }

    /**
     * @group Verifone_Payment
     */
    public function testIsAvailableActive()
    {
        $this->_scopeConfig->expects($this->at(0))->method('getValue')->willReturn(1);
        $this->assertTrue($this->_model->isAvailable($this->_getQuoteMock()));
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function _getQuoteMock()
    {
        return $this->getMockBuilder(\Magento\Quote\Api\Data\CartInterface::class)
            ->setMethods(['getStoreId'])
            ->getMockForAbstractClass();
    }
}