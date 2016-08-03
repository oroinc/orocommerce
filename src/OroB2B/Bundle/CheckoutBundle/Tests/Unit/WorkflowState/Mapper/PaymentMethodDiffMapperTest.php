<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\WorkflowState\Mapper;

use OroB2B\Bundle\CheckoutBundle\WorkflowState\Mapper\PaymentMethodDiffMapper;
use OroB2B\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use OroB2B\Bundle\PaymentBundle\Method\PaymentMethodRegistry;

class PaymentMethodDiffMapperTest extends AbstractCheckoutDiffMapperTest
{
    /**
     * @var PaymentMethodRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentMethodRegistry;

    /**
     * @var PaymentMethodInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentMethod;

    protected function setUp()
    {
        parent::setUp();

        $this->paymentMethod = $this->getMock('\OroB2B\Bundle\PaymentBundle\Method\PaymentMethodInterface');
        $this->paymentMethodRegistry = $this->getMock('OroB2B\Bundle\PaymentBundle\Method\PaymentMethodRegistry');

        $this->paymentMethodRegistry
            ->expects($this->any())
            ->method('getPaymentMethod')
            ->willReturn($this->paymentMethod);

        $this->mapper = new PaymentMethodDiffMapper($this->paymentMethodRegistry);
    }

    protected function tearDown()
    {
        parent::tearDown();

        unset($this->paymentMethodRegistry, $this->paymentMethod);
    }

    public function testGetName()
    {
        $this->assertEquals('paymentMethod', $this->mapper->getName());
    }

    public function testGetCurrentState()
    {
        $this->checkout
            ->expects($this->once())
            ->method('getPaymentMethod')
            ->willReturn('payflow_gateway');

        $result = $this->mapper->getCurrentState($this->checkout);

        $this->assertEquals('payflow_gateway', $result);
    }

    public function testIsStateActualTrue()
    {
        $this->paymentMethod
            ->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->checkout
            ->expects($this->once())
            ->method('getPaymentMethod')
            ->willReturn('payflow_gateway');

        $savedState = [
            'parameter1' => 10,
            'paymentMethod' => 'payflow_gateway',
            'parameter3' => 'green',
        ];

        $result = $this->mapper->isStateActual($this->checkout, $savedState);

        $this->assertEquals(true, $result);
    }

    public function testIsStateActualFalse()
    {
        $this->paymentMethod
            ->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->checkout
            ->expects($this->once())
            ->method('getPaymentMethod')
            ->willReturn('paypal_payments_pro');

        $savedState = [
            'parameter1' => 10,
            'paymentMethod' => 'payflow_gateway',
            'parameter3' => 'green',
        ];

        $result = $this->mapper->isStateActual($this->checkout, $savedState);

        $this->assertEquals(false, $result);
    }

    public function testIsStateActualParameterDoesntExist()
    {
        $this->paymentMethod
            ->expects($this->never())
            ->method('isEnabled');

        $this->checkout
            ->expects($this->never())
            ->method('getPaymentMethod');

        $savedState = [
            'parameter1' => 10,
            'parameter3' => 'green',
        ];

        $result = $this->mapper->isStateActual($this->checkout, $savedState);

        $this->assertEquals(true, $result);
    }

    public function testIsStateActualParameterOfWrongType()
    {
        $this->paymentMethod
            ->expects($this->never())
            ->method('isEnabled');

        $this->checkout
            ->expects($this->never())
            ->method('getPaymentMethod');

        $savedState = [
            'parameter1' => 10,
            'paymentMethod' => 1,
            'parameter3' => 'green',
        ];

        $result = $this->mapper->isStateActual($this->checkout, $savedState);

        $this->assertEquals(true, $result);
    }

    public function testIsStateActualPaymentMethodNotEnabled()
    {
        $this->paymentMethod
            ->expects($this->once())
            ->method('isEnabled')
            ->willReturn(false);

        $this->checkout
            ->expects($this->never())
            ->method('getPaymentMethod');

        $savedState = [
            'parameter1' => 10,
            'paymentMethod' => 'payflow_gateway',
            'parameter3' => 'green',
        ];

        $result = $this->mapper->isStateActual($this->checkout, $savedState);

        $this->assertEquals(false, $result);
    }

    public function testIsStateActualPaymentMethodInvalid()
    {
        $this->paymentMethodRegistry
            ->expects($this->once())
            ->method('getPaymentMethod')
            ->willThrowException(new \InvalidArgumentException);

        $this->checkout
            ->expects($this->never())
            ->method('getPaymentMethod');

        $savedState = [
            'parameter1' => 10,
            'paymentMethod' => 'payflow_gateway',
            'parameter3' => 'green',
        ];

        $result = $this->mapper->isStateActual($this->checkout, $savedState);

        $this->assertEquals(true, $result);
    }
}
