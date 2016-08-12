<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\WorkflowState\Mapper;

use OroB2B\Bundle\CheckoutBundle\WorkflowState\Mapper\ShipUntilDiffMapper;

class ShipUntilDiffMapperTest extends AbstractCheckoutDiffMapperTest
{
    protected function setUp()
    {
        parent::setUp();

        $this->mapper = new ShipUntilDiffMapper();
    }

    public function testGetName()
    {
        $this->assertEquals('shipUntil', $this->mapper->getName());
    }

    public function testGetCurrentState()
    {
        $now = new \DateTimeImmutable();

        $this->checkout
            ->expects($this->once())
            ->method('getShipUntil')
            ->willReturn($now);

        $result = $this->mapper->getCurrentState($this->checkout);

        $this->assertEquals($now, $result);
    }

    public function testIsStatesEqualTrue()
    {
        $state1 = [
            'parameter1' => 10,
            'shipUntil' => new \DateTime('2016-01-01'),
            'parameter3' => 'green',
        ];

        $state2 = [
            'parameter1' => 10,
            'shipUntil' => new \DateTime('2016-01-01'),
            'parameter3' => 'green',
        ];

        $this->assertEquals(true, $this->mapper->isStatesEqual($state1, $state2));
    }

    public function testIsStatesEqualFalse()
    {
        $state1 = [
            'parameter1' => 10,
            'shipUntil' => new \DateTime(),
            'parameter3' => 'green',
        ];

        $state2 = [
            'parameter1' => 10,
            'shipUntil' => new \DateTime('2016-01-01'),
            'parameter3' => 'green',
        ];

        $this->assertEquals(false, $this->mapper->isStatesEqual($state1, $state2));
    }

    public function testIsStatesEqualParameterNotExistInState1()
    {
        $state1 = [
            'parameter1' => 10,
            'parameter3' => 'green',
        ];

        $state2 = [
            'parameter1' => 10,
            'shipUntil' => new \DateTime(),
            'parameter3' => 'green',
        ];

        $this->assertEquals(true, $this->mapper->isStatesEqual($state1, $state2));
    }

    public function testIsStatesEqualParameterNotExistInState2()
    {
        $state1 = [
            'parameter1' => 10,
            'parameter3' => 'green',
            'shipUntil' => new \DateTime(),
        ];

        $state2 = [
            'parameter1' => 10,
            'parameter3' => 'green',
        ];

        $this->assertEquals(true, $this->mapper->isStatesEqual($state1, $state2));
    }
}
