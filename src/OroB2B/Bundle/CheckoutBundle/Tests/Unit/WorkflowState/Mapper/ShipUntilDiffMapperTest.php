<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\WorkflowState\Mapper;

use OroB2B\Bundle\CheckoutBundle\WorkflowState\Mapper\ShipUntilDiffMapper;

class ShipUntilDiffMapperTest extends AbstractCheckoutDiffMapperTest
{
    public function setUp()
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

    public function testIsStateActualTrue()
    {
        $now = new \DateTimeImmutable();

        $this->checkout
            ->expects($this->once())
            ->method('getShipUntil')
            ->willReturn($now);

        $savedState = [
            'parameter1' => 10,
            'shipUntil' => $now,
            'parameter3' => 'green',
        ];

        $result = $this->mapper->isStateActual($this->checkout, $savedState);

        $this->assertEquals(true, $result);
    }

    public function testIsStateActualFalse()
    {
        $now = new \DateTimeImmutable();

        $this->checkout
            ->expects($this->once())
            ->method('getShipUntil')
            ->willReturn($now->modify('-1 minute'));

        $savedState = [
            'parameter1' => 10,
            'shipUntil' => $now,
            'parameter3' => 'green',
        ];

        $result = $this->mapper->isStateActual($this->checkout, $savedState);

        $this->assertEquals(false, $result);
    }

    public function testIsStateActualParameterDoesntExist()
    {
        $this->checkout
            ->expects($this->never())
            ->method('getShipUntil');

        $savedState = [
            'parameter1' => 10,
            'parameter3' => 'green',
        ];

        $result = $this->mapper->isStateActual($this->checkout, $savedState);

        $this->assertEquals(true, $result);
    }

    public function testIsStateActualParameterOfWrongType()
    {
        $this->checkout
            ->expects($this->never())
            ->method('getShipUntil');

        $savedState = [
            'parameter1' => 10,
            'shipUntil' => 1,
            'parameter3' => 'green',
        ];

        $result = $this->mapper->isStateActual($this->checkout, $savedState);

        $this->assertEquals(true, $result);
    }
}
