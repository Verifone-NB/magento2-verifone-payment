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

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Verifone\Payment\Model\ConfigProvider;
use Verifone\Payment\Model\Payment;

class ConfigProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ObjectManager
     */
    protected $_objectManager;

    /**
     * @var \Magento\Payment\Helper\Data
     */
    protected $_paymentHelper;

    /**
     * @var ConfigProvider
     */
    protected $_model;

    /**
     * @var Payment
     */
    protected $_paymentInstance;

    public function setUp()
    {
        $this->_objectManager = new ObjectManager($this);
        $this->_paymentHelper = $this->getMockBuilder(\Magento\Payment\Helper\Data::class)->disableOriginalConstructor()->getMock();
        $this->_model = $this->_objectManager->getObject(ConfigProvider::class, [
            'paymentHelper' => $this->_paymentHelper
        ]);
    }

    /**
     * @group Verifone_Payment
     */
    public function testGetConfigUnavailable()
    {
        $paymentMethodMock = $this->_getPaymentMethodMock();
        $paymentMethodMock->expects($this->once())->method('isAvailable')->willReturn(false);
        $this->_paymentHelper->expects($this->once())->method('getMethodInstance')->with($this->equalTo('verifone_payment'))->willReturn($paymentMethodMock);
        $this->assertEquals([], $this->_model->getConfig());
        //        $payment->expects($this->once())->method('getCheckoutRedirectUrl')->willReturn('http://redirect.url');
        //        $paymentHelper->expects($this->once())->method('getMethodInstance')->with($this->equalTo('verifone_payment'))->willReturn($payment);
        //        $model->getConfig();
    }

    /**
     * @group Verifone_Payment
     */
    public function testGetConfigAvailable()
    {
        $redirectUrl = 'http://redirect.url';
        $expectedConfig = [
            'payment' => [
                'verifonePayment' => [
                    'redirectUrl' => $redirectUrl,
                    'paytypes' => null
                ]
            ]
        ];
        $paymentMethodMock = $this->_getPaymentMethodMock();
        $paymentMethodMock->expects($this->once())->method('isAvailable')->willReturn(true);
        $paymentMethodMock->expects($this->once())->method('getCheckoutRedirectUrl')->willReturn($redirectUrl);
        $this->_paymentHelper->expects($this->once())->method('getMethodInstance')->with($this->equalTo('verifone_payment'))->willReturn($paymentMethodMock);
        $this->assertEquals($expectedConfig, $this->_model->getConfig());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function _getPaymentMethodMock()
    {
        return $this->getMockBuilder(Payment::class)
            ->setMethods([
                'isAvailable',
                'getCheckoutRedirectUrl'
            ])
            ->disableOriginalConstructor()
            ->getMock();
    }
}