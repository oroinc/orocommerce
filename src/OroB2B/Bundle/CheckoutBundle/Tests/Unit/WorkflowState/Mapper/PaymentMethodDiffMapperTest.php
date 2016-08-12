<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\WorkflowState\Mapper;

use OroB2B\Bundle\CheckoutBundle\WorkflowState\Mapper\PaymentMethodDiffMapper;

class PaymentMethodDiffMapperTest extends AbstractCheckoutDiffMapperTest
{
    protected function setUp()
    {
        parent::setUp();

        $this->mapper = new PaymentMethodDiffMapper();
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

        $this->assertEquals('payflow_gateway', $this->mapper->getCurrentState($this->checkout));
    }

    public function testIsStatesEqualTrue()
    {
        $state1 = [
            'parameter1' => 10,
            'paymentMethod' => 'payflow_gateway',
            'parameter3' => 'green',
        ];

        $state2 = [
            'parameter1' => 10,
            'paymentMethod' => 'payflow_gateway',
            'parameter3' => 'green',
        ];

        $entity = new \stdClass();

        $this->assertEquals(true, $this->mapper->isStatesEqual($entity, $state1, $state2));
    }

    public function testIsStatesEqualFalse()
    {
        $state1 = [
            'parameter1' => 10,
            'paymentMethod' => 'payflow_gateway',
            'parameter3' => 'green',
        ];

        $state2 = [
            'parameter1' => 10,
            'paymentMethod' => 'creditCard',
            'parameter3' => 'green',
        ];

        $entity = new \stdClass();

        $this->assertEquals(false, $this->mapper->isStatesEqual($entity, $state1, $state2));
    }

    public function testIsStatesEqualParameterNotExistInState1()
    {
        $state1 = [
            'parameter1' => 10,
            'parameter3' => 'green',
        ];

        $state2 = [
            'parameter1' => 10,
            'paymentMethod' => 'payflow_gateway',
            'parameter3' => 'green',
        ];

        $entity = new \stdClass();

        $this->assertEquals(true, $this->mapper->isStatesEqual($entity, $state1, $state2));
    }

    public function testIsStatesEqualParameterNotExistInState2()
    {
        $state1 = [
            'parameter1' => 10,
            'parameter3' => 'green',
            'paymentMethod' => 'payflow_gateway',
        ];

        $state2 = [
            'parameter1' => 10,
            'parameter3' => 'green',
        ];

        $entity = new \stdClass();

        $this->assertEquals(true, $this->mapper->isStatesEqual($entity, $state1, $state2));
    }
}
