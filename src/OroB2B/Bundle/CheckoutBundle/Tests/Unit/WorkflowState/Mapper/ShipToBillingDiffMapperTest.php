<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\WorkflowState\Mapper;

use OroB2B\Bundle\CheckoutBundle\WorkflowState\Mapper\ShipToBillingDiffMapper;
use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;

/**
 * @SuppressWarnings("TooManyPublicMethods")
 */
class ShipToBillingDiffMapperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ShipToBillingDiffMapper
     */
    protected $mapper;

    /**
     * @var Checkout|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $checkout;

    public function setUp()
    {
        $this->mapper = new ShipToBillingDiffMapper();
        $this->checkout = $this->getMock('OroB2B\Bundle\CheckoutBundle\Entity\Checkout');
    }

    public function tearDown()
    {
        unset($this->mapper, $this->checkout);
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
        $this->assertEquals('shipToBillingAddress', $this->mapper->getName());
    }

    public function testGetCurrentState()
    {
        $this->checkout->expects($this->once())
            ->method('isShipToBillingAddress')
            ->willReturn(true);

        $this->assertEquals(true, $this->mapper->getCurrentState($this->checkout));
    }

    public function testGetCurrentStateWithFalse()
    {
        $this->checkout->expects($this->once())
            ->method('isShipToBillingAddress')
            ->willReturn(false);

        $this->assertEquals(false, $this->mapper->getCurrentState($this->checkout));
    }

    public function testIsStateActualTrue()
    {
        $this->checkout
            ->expects($this->once())
            ->method('isShipToBillingAddress')
            ->willReturn(true);

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
        $this->checkout
            ->expects($this->once())
            ->method('isShipToBillingAddress')
            ->willReturn(false);

        $savedState = [
            'parameter1' => 10,
            'shipToBillingAddress' => true,
            'parameter3' => 'green',
        ];

        $result = $this->mapper->isStateActual($this->checkout, $savedState);

        $this->assertEquals(false, $result);
    }

    public function testIsStateActualParameterNotExist()
    {
        $this->checkout
            ->expects($this->never())
            ->method('isShipToBillingAddress');

        $savedState = [
            'parameter1' => 10,
            'parameter3' => 'green',
        ];

        $result = $this->mapper->isStateActual($this->checkout, $savedState);

        $this->assertEquals(true, $result);
    }

    public function testIsStateActualParameterOfWrongType()
    {
        $this->checkout->expects($this->never())
            ->method('isShipToBillingAddress');

        $savedState = [
            'parameter1' => 10,
            'shipToBillingAddress' => 1,
            'parameter3' => 'green',
        ];

        $result = $this->mapper->isStateActual($this->checkout, $savedState);

        $this->assertEquals(true, $result);
    }
}
