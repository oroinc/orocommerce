<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\WorkflowState\Mapper;

use OroB2B\Bundle\CheckoutBundle\WorkflowState\Mapper\PaymentMethodDiffMapper;
use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;

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

    public function setUp()
    {
        $this->mapper = new PaymentMethodDiffMapper();
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
        $this->checkout->method('getPaymentMethod')->willReturn(123);
        $savedState = [
            'parameter1' => 10,
            'paymentMethod' => 'payflow_gateway',
            'parameter3' => 'green',
        ];

        $result = $this->mapper->compareStates($this->checkout, $savedState);

        $this->assertEquals(false, $result);
    }
}
