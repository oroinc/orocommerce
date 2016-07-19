<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\WorkflowState\Mapper;

use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;
use OroB2B\Bundle\CheckoutBundle\WorkflowState\Mapper\ShipUntilDiffMapper;

class ShipUntillDiffMapperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ShipUntilDiffMapper
     */
    private $mapper;

    /**
     * @var Checkout|\PHPUnit_Framework_MockObject_MockObject
     */
    private $checkout;

    public function setUp()
    {
        $this->mapper = new ShipUntilDiffMapper();
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
        $this->assertEquals('shipUntil', $this->mapper->getName());
    }

    public function testGetCurrentState()
    {
        $now = new \DateTimeImmutable();
        $this->checkout->method('getShipUntil')->willReturn($now);

        $result = $this->mapper->getCurrentState($this->checkout);

        $this->assertEquals($now, $result);
    }

    public function testIsStateActualTrue()
    {
        $now = new \DateTimeImmutable();
        $this->checkout->method('getShipUntil')->willReturn($now);
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
        $this->checkout->method('getShipUntil')->willReturn($now->modify('-1 minute'));
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
        $now = new \DateTimeImmutable();
        $this->checkout->method('getShipUntil')->willReturn($now);
        $savedState = [
            'parameter1' => 10,
            'parameter3' => 'green',
        ];

        $result = $this->mapper->isStateActual($this->checkout, $savedState);

        $this->assertEquals(false, $result);
    }

    public function testIsStateActualParameterOfWrongType()
    {
        $now = new \DateTimeImmutable();
        $this->checkout->method('getShipUntil')->willReturn($now);
        $savedState = [
            'parameter1' => 10,
            'shipUntil' => 1,
            'parameter3' => 'green',
        ];

        $result = $this->mapper->isStateActual($this->checkout, $savedState);

        $this->assertEquals(false, $result);
    }
}
