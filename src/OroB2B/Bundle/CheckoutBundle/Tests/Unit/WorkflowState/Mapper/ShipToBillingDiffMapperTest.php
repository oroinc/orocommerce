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

    public function testGetPriority()
    {
        $this->assertEquals(10, $this->mapper->getPriority());
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
        $this->checkout->method('isShipToBillingAddress')->willReturn(true);

        $result = $this->mapper->getCurrentState($this->checkout);

        $this->assertEquals(
            ['shipToBillingAddress' => true],
            $result
        );
    }

    public function testCompareStatesTrue()
    {
        $this->checkout->method('isShipToBillingAddress')->willReturn(true);
        $savedState = [
            'parameter1' => 10,
            'shipToBillingAddress' => true,
            'parameter3' => 'green',
        ];

        $result = $this->mapper->compareStates($this->checkout, $savedState);

        $this->assertEquals(true, $result);
    }

    public function testCompareStatesFalse()
    {
        $this->checkout->method('isShipToBillingAddress')->willReturn(false);
        $savedState = [
            'parameter1' => 10,
            'shipToBillingAddress' => true,
            'parameter3' => 'green',
        ];

        $result = $this->mapper->compareStates($this->checkout, $savedState);

        $this->assertEquals(false, $result);
    }

    public function testCompareStatesParameterDoesntExist()
    {
        $this->checkout->method('isShipToBillingAddress')->willReturn(true);
        $savedState = [
            'parameter1' => 10,
            'parameter3' => 'green',
        ];

        $result = $this->mapper->compareStates($this->checkout, $savedState);

        $this->assertEquals(false, $result);
    }

    public function testCompareStatesParameterOfWrongType()
    {
        $this->checkout->method('isShipToBillingAddress')->willReturn(true);
        $savedState = [
            'parameter1' => 10,
            'shipToBillingAddress' => 1,
            'parameter3' => 'green',
        ];

        $result = $this->mapper->compareStates($this->checkout, $savedState);

        $this->assertEquals(false, $result);
    }
}
