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

    public function testGetPriority()
    {
        $this->assertEquals(40, $this->mapper->getPriority());
    }

    public function testIsEntitySupported()
    {
        $this->assertEquals(true, $this->mapper->isEntitySupported($this->checkout));
    }

    public function testIsEntitySupportedUnsopportedEntity()
    {
        $entity = new \stdClass();

        $this->assertEquals(false, $this->mapper->isEntitySupported($entity));
    }

    public function testGetCurrentState()
    {
        $this->checkout->method('getPaymentMethod')->willReturn('payflow_gateway');

        $result = $this->mapper->getCurrentState($this->checkout);

        $this->assertEquals(
            ['paymentMethod' => 'payflow_gateway'],
            $result
        );
    }

    public function testCompareStatesTrue()
    {
        $this->paymentMethod->method('isEnabled')->willReturn(true);
        $this->checkout->method('getPaymentMethod')->willReturn('payflow_gateway');
        $savedState = [
            'parameter1' => 10,
            'paymentMethod' => 'payflow_gateway',
            'parameter3' => 'green',
        ];

        $result = $this->mapper->compareStates($this->checkout, $savedState);

        $this->assertEquals(true, $result);
    }

    public function testCompareStatesFalse()
    {
        $this->paymentMethod->method('isEnabled')->willReturn(true);
        $this->checkout->method('getPaymentMethod')->willReturn('paypal_payments_pro');
        $savedState = [
            'parameter1' => 10,
            'paymentMethod' => 'payflow_gateway',
            'parameter3' => 'green',
        ];

        $result = $this->mapper->compareStates($this->checkout, $savedState);

        $this->assertEquals(false, $result);
    }

    public function testCompareStatesParameterDoesntExist()
    {
        $this->paymentMethod->method('isEnabled')->willReturn(true);
        $this->checkout->method('getPaymentMethod')->willReturn('payflow_gateway');
        $savedState = [
            'parameter1' => 10,
            'parameter3' => 'green',
        ];

        $result = $this->mapper->compareStates($this->checkout, $savedState);

        $this->assertEquals(false, $result);
    }

    public function testCompareStatesParameterOfWrongType()
    {
        $this->paymentMethod->method('isEnabled')->willReturn(true);
        $this->checkout->method('getPaymentMethod')->willReturn(123);
        $savedState = [
            'parameter1' => 10,
            'paymentMethod' => 'payflow_gateway',
            'parameter3' => 'green',
        ];

        $result = $this->mapper->compareStates($this->checkout, $savedState);

        $this->assertEquals(false, $result);
    }

    public function testCompareStatesPaymentMethodNotEnabled()
    {
        $this->paymentMethod->method('isEnabled')->willReturn(false);
        $this->checkout->method('getPaymentMethod')->willReturn('payflow_gateway');
        $savedState = [
            'parameter1' => 10,
            'paymentMethod' => 'payflow_gateway',
            'parameter3' => 'green',
        ];

        $result = $this->mapper->compareStates($this->checkout, $savedState);

        $this->assertEquals(false, $result);
    }

    public function testCompareStatesPaymentMethodInvalid()
    {
        $this->paymentMethodRegistry->method('getPaymentMethod')->willThrowException(new \InvalidArgumentException);
        $this->checkout->method('getPaymentMethod')->willReturn('payflow_gateway');
        $savedState = [
            'parameter1' => 10,
            'paymentMethod' => 'payflow_gateway',
            'parameter3' => 'green',
        ];

        $result = $this->mapper->compareStates($this->checkout, $savedState);

        $this->assertEquals(false, $result);
    }
}
