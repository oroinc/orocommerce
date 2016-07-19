<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\WorkflowState\Mapper;

use OroB2B\Bundle\CheckoutBundle\WorkflowState\Mapper\ShipToBillingDiffMapper;
use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;

class ShipToBillingDiffMapperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ShipToBillingDiffMapper
     */
    private $mapper;

    /**
     * @var Checkout|\PHPUnit_Framework_MockObject_MockObject
     */
    private $checkout;

    public function setUp()
    {
        $this->mapper = new ShipToBillingDiffMapper();
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

    public function testIsEntitySupportedUnsopportedEntity()
    {
        $entity = new \stdClass();

        $this->assertEquals(false, $this->mapper->isEntitySupported($entity));
    }

    public function testGetName()
    {
        $this->assertEquals('shipToBillingAddress', $this->mapper->getName());
    }

    public function testGetCurrentState()
    {
        $this->checkout->method('isShipToBillingAddress')->willReturn(true);

        $result = $this->mapper->getCurrentState($this->checkout);

        $this->assertEquals(true, $result);
    }

    public function testIsStateActualTrue()
    {
        $this->checkout->method('isShipToBillingAddress')->willReturn(true);
        $savedState = [
            'parameter1' => 10,
            'shipToBillingAddress' => true,
            'parameter3' => 'green',
        ];

        $result = $this->mapper->isStateActual($this->checkout, $savedState);

        $this->assertEquals(true, $result);
    }

    public function testIsStateActualFalse()
    {
        $this->checkout->method('isShipToBillingAddress')->willReturn(false);
        $savedState = [
            'parameter1' => 10,
            'shipToBillingAddress' => true,
            'parameter3' => 'green',
        ];

        $result = $this->mapper->isStateActual($this->checkout, $savedState);

        $this->assertEquals(false, $result);
    }

    public function testIsStateActualParameterDoesntExist()
    {
        $this->checkout->method('isShipToBillingAddress')->willReturn(true);
        $savedState = [
            'parameter1' => 10,
            'parameter3' => 'green',
        ];

        $result = $this->mapper->isStateActual($this->checkout, $savedState);

        $this->assertEquals(false, $result);
    }

    public function testIsStateActualParameterOfWrongType()
    {
        $this->checkout->method('isShipToBillingAddress')->willReturn(true);
        $savedState = [
            'parameter1' => 10,
            'shipToBillingAddress' => 1,
            'parameter3' => 'green',
        ];

        $result = $this->mapper->isStateActual($this->checkout, $savedState);

        $this->assertEquals(false, $result);
    }
}
