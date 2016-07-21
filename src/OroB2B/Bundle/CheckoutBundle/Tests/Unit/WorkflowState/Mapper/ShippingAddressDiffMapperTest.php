<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\WorkflowState\Mapper;

use OroB2B\Bundle\CheckoutBundle\WorkflowState\Mapper\ShippingAddressDiffMapper;
use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;
use OroB2B\Bundle\OrderBundle\Entity\OrderAddress;

/**
 * @SuppressWarnings("TooManyPublicMethods")
 */
class ShippingAddressDiffMapperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ShippingAddressDiffMapper
     */
    protected $mapper;

    /**
     * @var OrderAddress|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $shippingAddress;

    public function setUp()
    {
        $this->mapper = new ShippingAddressDiffMapper();
        $this->checkout = $this->getMock('OroB2B\Bundle\CheckoutBundle\Entity\Checkout');
        $this->shippingAddress = $this->getMock('OroB2B\Bundle\OrderBundle\Entity\OrderAddress');
    }

    public function tearDown()
    {
        unset($this->mapper, $this->checkout, $this->shippingAddress);
    }

    /**
     * @param mixed $shippingAddress
     * @return Checkout|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getCheckout($shippingAddress)
    {
        $checkout = $this->getMock('OroB2B\Bundle\CheckoutBundle\Entity\Checkout');

        $checkout
            ->expects($this->any())
            ->method('getShippingAddress')
            ->willReturn($shippingAddress);

        return $checkout;
    }

    public function testIsEntitySupported()
    {
        $this->assertEquals(
            true,
            $this->mapper->isEntitySupported(
                $this->getCheckout($shippingAddress = $this->shippingAddress)
            )
        );
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
        $this->assertEquals('shippingAddress', $this->mapper->getName());
    }

    public function testGetCurrentState()
    {
        $this->shippingAddress
            ->expects($this->once())
            ->method('getId')
            ->willReturn(123);

        $now = new \DateTimeImmutable();

        $this->shippingAddress
            ->expects($this->once())
            ->method('getUpdated')
            ->willReturn($now);

        $result = $this->mapper->getCurrentState(
            $this->getCheckout($shippingAddress = $this->shippingAddress)
        );

        $this->assertEquals(
            [
                'id' => 123,
                'updated' => $now,
            ],
            $result
        );
    }

    public function testGetCurrentStateEmptyShippingAddress()
    {
        $result = $this->mapper->getCurrentState(
            $this->getCheckout($shippingAddress = null)
        );

        $this->assertEquals([], $result);
    }

    public function testIsStateActualTrue()
    {
        $this->shippingAddress
            ->expects($this->once())
            ->method('getId')
            ->willReturn(123);

        $now = new \DateTimeImmutable();

        $this->shippingAddress
            ->expects($this->once())
            ->method('getUpdated')
            ->willReturn($now->modify('-1 minute'));

        $savedState = [
            'parameter1' => 10,
            'shippingAddress' => [
                'id' => 123,
                'updated' => $now,
            ],
            'parameter3' => 'green',
        ];

        $result = $this->mapper->isStateActual(
            $this->getCheckout($shippingAddress = $this->shippingAddress),
            $savedState
        );

        $this->assertEquals(true, $result);
    }

    public function testIsStateActualFalseId()
    {
        $this->shippingAddress
            ->expects($this->once())
            ->method('getId')
            ->willReturn(123);

        $now = new \DateTimeImmutable();

        $this->shippingAddress
            ->expects($this->never())
            ->method('getUpdated')
            ->willReturn($now->modify('-1 minute'));

        $savedState = [
            'parameter1' => 10,
            'shippingAddress' => [
                'id' => 124,
                'updated' => $now,
            ],
            'parameter3' => 'green',
        ];

        $result = $this->mapper->isStateActual(
            $this->getCheckout($shippingAddress = $this->shippingAddress),
            $savedState
        );

        $this->assertEquals(false, $result);
    }

    public function testIsStateActualFalseUpdated()
    {
        $this->shippingAddress
            ->expects($this->once())
            ->method('getId')
            ->willReturn(123);

        $now = new \DateTimeImmutable();

        $this->shippingAddress
            ->expects($this->once())
            ->method('getUpdated')
            ->willReturn($now->modify('+1 minute'));

        $savedState = [
            'parameter1' => 10,
            'shippingAddress' => [
                'id' => 123,
                'updated' => $now,
            ],
            'parameter3' => 'green',
        ];

        $result = $this->mapper->isStateActual(
            $this->getCheckout($shippingAddress = $this->shippingAddress),
            $savedState
        );

        $this->assertEquals(false, $result);
    }

    public function testIsStateActualParameterDoesntExist()
    {
        $this->shippingAddress
            ->expects($this->never())
            ->method('getId')
            ->willReturn(123);

        $now = new \DateTimeImmutable();

        $this->shippingAddress
            ->expects($this->never())
            ->method('getUpdated')
            ->willReturn($now->modify('-1 minute'));

        $savedState = [
            'parameter1' => 10,
            'parameter3' => 'green',
        ];

        $result = $this->mapper->isStateActual(
            $this->getCheckout($shippingAddress = $this->shippingAddress),
            $savedState
        );

        $this->assertEquals(false, $result);
    }

    public function testIsStateActualParameterOfWrongType()
    {
        $this->shippingAddress
            ->expects($this->never())
            ->method('getId')
            ->willReturn(123);

        $now = new \DateTimeImmutable();

        $this->shippingAddress
            ->expects($this->never())
            ->method('getUpdated')
            ->willReturn($now->modify('-1 minute'));

        $savedState = [
            'parameter1' => 10,
            'shippingAddress' => 1,
            'parameter3' => 'green',
        ];

        $result = $this->mapper->isStateActual(
            $this->getCheckout($shippingAddress = $this->shippingAddress),
            $savedState
        );

        $this->assertEquals(false, $result);
    }

    public function testIsStateActualParameterOfWrongTypeUpdated()
    {
        $this->shippingAddress
            ->expects($this->never())
            ->method('getId')
            ->willReturn(123);

        $now = new \DateTimeImmutable();

        $this->shippingAddress
            ->expects($this->never())
            ->method('getUpdated')
            ->willReturn($now->modify('-1 minute'));

        $savedState = [
            'parameter1' => 10,
            'shippingAddress' => [
                'id' => 123,
                'updated' => 'yesterday',
            ],
            'parameter3' => 'green',
        ];

        $result = $this->mapper->isStateActual(
            $this->getCheckout($shippingAddress = $this->shippingAddress),
            $savedState
        );

        $this->assertEquals(false, $result);
    }

    public function testIsStateActualParameterNotSetId()
    {
        $this->shippingAddress
            ->expects($this->never())
            ->method('getId')
            ->willReturn(123);

        $now = new \DateTimeImmutable();

        $this->shippingAddress
            ->expects($this->never())
            ->method('getUpdated')
            ->willReturn($now->modify('-1 minute'));

        $savedState = [
            'parameter1' => 10,
            'shippingAddress' => [
                'updated' => new \DateTimeImmutable(),
            ],
            'parameter3' => 'green',
        ];

        $result = $this->mapper->isStateActual(
            $this->getCheckout($shippingAddress = $this->shippingAddress),
            $savedState
        );

        $this->assertEquals(false, $result);
    }

    public function testIsStateActualParameterNotSetUpdated()
    {
        $this->shippingAddress
            ->expects($this->never())
            ->method('getId')
            ->willReturn(123);

        $now = new \DateTimeImmutable();

        $this->shippingAddress
            ->expects($this->never())
            ->method('getUpdated')
            ->willReturn($now->modify('-1 minute'));

        $savedState = [
            'parameter1' => 10,
            'shippingAddress' => [
                'id' => 123,
            ],
            'parameter3' => 'green',
        ];

        $result = $this->mapper->isStateActual(
            $this->getCheckout($shippingAddress = $this->shippingAddress),
            $savedState
        );

        $this->assertEquals(false, $result);
    }
}
