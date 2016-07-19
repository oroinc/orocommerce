<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\WorkflowState\Mapper;

use OroB2B\Bundle\CheckoutBundle\WorkflowState\Mapper\PaymentMethodDiffMapper;
use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;
use OroB2B\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use OroB2B\Bundle\PaymentBundle\Method\PaymentMethodRegistry;

class PaymentMethodDiffMapperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PaymentMethodDiffMapper
     */
    private $mapper;

    /**
     * @var Checkout|\PHPUnit_Framework_MockObject_MockObject
     */
    private $checkout;

    /**
     * @var PaymentMethodRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentMethodRegistry;

    /**
     * @var PaymentMethodInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentMethod;

    public function setUp()
    {
        $this->paymentMethod = $this->getMock('\OroB2B\Bundle\PaymentBundle\Method\PaymentMethodInterface');
        $this->paymentMethodRegistry = $this->getMock('OroB2B\Bundle\PaymentBundle\Method\PaymentMethodRegistry');
        $this->paymentMethodRegistry->method('getPaymentMethod')->willReturn($this->paymentMethod);
        $this->mapper = new PaymentMethodDiffMapper($this->paymentMethodRegistry);
        $this->checkout = $this->getMock('OroB2B\Bundle\CheckoutBundle\Entity\Checkout');
    }

    public function testIsEntitySupported()
    {
        $this->assertEquals(true, $this->mapper->isEntitySupported($this->checkout));
    }

    public function testIsEntitySupportedNotObject()
    {
        $entity = 'string';

        $this->assertEquals(false, $this->mapper->isEntitySupported($entity));
    }

    public function testIsEntitySupportedUnsupportedEntity()
    {
        $entity = new \stdClass();

        $this->assertEquals(false, $this->mapper->isEntitySupported($entity));
    }

    public function testGetName()
    {
        $this->assertEquals('paymentMethod', $this->mapper->getName());
    }

    public function testGetCurrentState()
    {
        $this->checkout->method('getPaymentMethod')->willReturn('payflow_gateway');

        $result = $this->mapper->getCurrentState($this->checkout);

        $this->assertEquals('payflow_gateway', $result);
    }

    public function testIsStateActualTrue()
    {
        $this->paymentMethod->method('isEnabled')->willReturn(true);
        $this->checkout->method('getPaymentMethod')->willReturn('payflow_gateway');
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
        $this->paymentMethod->method('isEnabled')->willReturn(true);
        $this->checkout->method('getPaymentMethod')->willReturn('paypal_payments_pro');
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
        $this->paymentMethod->method('isEnabled')->willReturn(true);
        $this->checkout->method('getPaymentMethod')->willReturn('payflow_gateway');
        $savedState = [
            'parameter1' => 10,
            'parameter3' => 'green',
        ];

        $result = $this->mapper->isStateActual($this->checkout, $savedState);

        $this->assertEquals(false, $result);
    }

    public function testIsStateActualParameterOfWrongType()
    {
        $this->paymentMethod->method('isEnabled')->willReturn(true);
        $this->checkout->method('getPaymentMethod')->willReturn(123);
        $savedState = [
            'parameter1' => 10,
            'paymentMethod' => 'payflow_gateway',
            'parameter3' => 'green',
        ];

        $result = $this->mapper->isStateActual($this->checkout, $savedState);

        $this->assertEquals(false, $result);
    }

    public function testIsStateActualPaymentMethodNotEnabled()
    {
        $this->paymentMethod->method('isEnabled')->willReturn(false);
        $this->checkout->method('getPaymentMethod')->willReturn('payflow_gateway');
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
        $this->paymentMethodRegistry->method('getPaymentMethod')->willThrowException(new \InvalidArgumentException);
        $this->checkout->method('getPaymentMethod')->willReturn('payflow_gateway');
        $savedState = [
            'parameter1' => 10,
            'paymentMethod' => 'payflow_gateway',
            'parameter3' => 'green',
        ];

        $result = $this->mapper->isStateActual($this->checkout, $savedState);

        $this->assertEquals(false, $result);
    }
}
