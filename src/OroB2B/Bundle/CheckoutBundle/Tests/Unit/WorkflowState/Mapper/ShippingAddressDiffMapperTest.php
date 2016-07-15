<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\WorkflowState\Mapper;

use OroB2B\Bundle\CheckoutBundle\WorkflowState\Mapper\ShippingAddressDiffMapper;
use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;
use OroB2B\Bundle\OrderBundle\Entity\OrderAddress;

class ShippingAddressDiffMapperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ShippingAddressDiffMapper
     */
    private $mapper;

    /**
     * @var Checkout|\PHPUnit_Framework_MockObject_MockObject
     */
    private $checkout;

    /**
     * @var OrderAddress|\PHPUnit_Framework_MockObject_MockObject
     */
    private $shippingAddress;

    public function setUp()
    {
        $this->mapper = new ShippingAddressDiffMapper();
        $this->checkout = $this->getMock('OroB2B\Bundle\CheckoutBundle\Entity\Checkout');
        $this->shippingAddress = $this->getMock('OroB2B\Bundle\OrderBundle\Entity\OrderAddress');
        $this->checkout->method('getShippingAddress')->willReturn($this->shippingAddress);
    }

    public function testGetPriority()
    {
        $this->assertEquals(30, $this->mapper->getPriority());
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
        $this->shippingAddress->method('getId')->willReturn(123);
        $now = new \DateTimeImmutable();
        $this->shippingAddress->method('getUpdated')->willReturn($now);

        $result = $this->mapper->getCurrentState($this->checkout);

        $this->assertEquals(
            ['shippingAddress' => [
                'id' => 123,
                'updated' => $now,
            ]],
            $result
        );
    }

    public function testCompareStatesTrue()
    {
        $this->shippingAddress->method('getId')->willReturn(123);
        $now = new \DateTimeImmutable();
        $this->shippingAddress->method('getUpdated')->willReturn($now->modify('-1 minute'));

        $savedState = [
            'parameter1' => 10,
            'shippingAddress' => [
                'id' => 123,
                'updated' => $now,
            ],
            'parameter3' => 'green',
        ];

        $result = $this->mapper->compareStates($this->checkout, $savedState);

        $this->assertEquals(true, $result);
    }

    public function testCompareStatesFalseId()
    {
        $this->shippingAddress->method('getId')->willReturn(123);
        $now = new \DateTimeImmutable();
        $this->shippingAddress->method('getUpdated')->willReturn($now->modify('-1 minute'));

        $savedState = [
            'parameter1' => 10,
            'shippingAddress' => [
                'id' => 124,
                'updated' => $now,
            ],
            'parameter3' => 'green',
        ];

        $result = $this->mapper->compareStates($this->checkout, $savedState);

        $this->assertEquals(false, $result);
    }

    public function testCompareStatesFalseUpdated()
    {
        $this->shippingAddress->method('getId')->willReturn(123);
        $now = new \DateTimeImmutable();
        $this->shippingAddress->method('getUpdated')->willReturn($now->modify('+1 minute'));

        $savedState = [
            'parameter1' => 10,
            'shippingAddress' => [
                'id' => 123,
                'updated' => $now,
            ],
            'parameter3' => 'green',
        ];

        $result = $this->mapper->compareStates($this->checkout, $savedState);

        $this->assertEquals(false, $result);
    }

    public function testCompareStatesParameterDoesntExist()
    {
        $this->shippingAddress->method('getId')->willReturn(123);
        $now = new \DateTimeImmutable();
        $this->shippingAddress->method('getUpdated')->willReturn($now->modify('-1 minute'));

        $savedState = [
            'parameter1' => 10,
            'parameter3' => 'green',
        ];

        $result = $this->mapper->compareStates($this->checkout, $savedState);

        $this->assertEquals(false, $result);
    }

    public function testCompareStatesParameterOfWrongType()
    {
        $this->shippingAddress->method('getId')->willReturn(123);
        $now = new \DateTimeImmutable();
        $this->shippingAddress->method('getUpdated')->willReturn($now->modify('-1 minute'));

        $savedState = [
            'parameter1' => 10,
            'shippingAddress' => 1,
            'parameter3' => 'green',
        ];

        $result = $this->mapper->compareStates($this->checkout, $savedState);

        $this->assertEquals(false, $result);
    }

    public function testCompareStatesParameterOfWrongTypeUpdated()
    {
        $this->shippingAddress->method('getId')->willReturn(123);
        $now = new \DateTimeImmutable();
        $this->shippingAddress->method('getUpdated')->willReturn($now->modify('-1 minute'));

        $savedState = [
            'parameter1' => 10,
            'shippingAddress' => [
                'id' => 123,
                'updated' => 'yesterday',
            ],
            'parameter3' => 'green',
        ];

        $result = $this->mapper->compareStates($this->checkout, $savedState);

        $this->assertEquals(false, $result);
    }

    public function testCompareStatesParameterNotSetId()
    {
        $this->shippingAddress->method('getId')->willReturn(123);
        $now = new \DateTimeImmutable();
        $this->shippingAddress->method('getUpdated')->willReturn($now->modify('-1 minute'));

        $savedState = [
            'parameter1' => 10,
            'shippingAddress' => [
                'updated' => new \DateTimeImmutable(),
            ],
            'parameter3' => 'green',
        ];

        $result = $this->mapper->compareStates($this->checkout, $savedState);

        $this->assertEquals(false, $result);
    }

    public function testCompareStatesParameterNotSetUpdated()
    {
        $this->shippingAddress->method('getId')->willReturn(123);
        $now = new \DateTimeImmutable();
        $this->shippingAddress->method('getUpdated')->willReturn($now->modify('-1 minute'));

        $savedState = [
            'parameter1' => 10,
            'shippingAddress' => [
                'id' => 123,
            ],
            'parameter3' => 'green',
        ];

        $result = $this->mapper->compareStates($this->checkout, $savedState);

        $this->assertEquals(false, $result);
    }
}
