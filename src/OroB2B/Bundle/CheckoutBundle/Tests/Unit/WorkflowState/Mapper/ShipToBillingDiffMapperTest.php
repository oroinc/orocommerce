<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\WorkflowState\Mapper;

use OroB2B\Bundle\CheckoutBundle\WorkflowState\Mapper\ShipToBillingDiffMapper;

class ShipToBillingDiffMapperTest extends AbstractCheckoutDiffMapperTest
{
    protected function setUp()
    {
        parent::setUp();

        $this->mapper = new ShipToBillingDiffMapper();
    }

    public function testGetName()
    {
        $this->assertEquals('shipToBillingAddress', $this->mapper->getName());
    }

    public function testGetCurrentState()
    {
        $this->checkout->expects($this->once())
            ->method('isShipToBillingAddress')
            ->willReturn(true);

        $this->assertEquals(true, $this->mapper->getCurrentState($this->checkout));
    }

    public function testIsStatesEqualTrue()
    {
        $state1 = [
            'parameter1' => 10,
            'shipToBillingAddress' => true,
            'parameter3' => 'green',
        ];

        $state2 = [
            'parameter1' => 10,
            'shipToBillingAddress' => true,
            'parameter3' => 'green',
        ];

        $entity = new \stdClass();

        $this->assertEquals(true, $this->mapper->isStatesEqual($entity, $state1, $state2));
    }

    public function testIsStatesEqualFalse()
    {
        $state1 = [
            'parameter1' => 10,
            'shipToBillingAddress' => true,
            'parameter3' => 'green',
        ];

        $state2 = [
            'parameter1' => 10,
            'shipToBillingAddress' => false,
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
            'shipToBillingAddress' => true,
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
            'shipToBillingAddress' => true,
        ];

        $state2 = [
            'parameter1' => 10,
            'parameter3' => 'green',
        ];

        $entity = new \stdClass();

        $this->assertEquals(true, $this->mapper->isStatesEqual($entity, $state1, $state2));
    }
}
