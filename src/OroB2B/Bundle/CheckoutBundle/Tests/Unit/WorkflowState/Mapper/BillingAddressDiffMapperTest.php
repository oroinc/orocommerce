<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\WorkflowState\Mapper;

use OroB2B\Bundle\CheckoutBundle\WorkflowState\Mapper\BillingAddressDiffMapper;
use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;
use OroB2B\Bundle\OrderBundle\Entity\OrderAddress;

/**
 * @SuppressWarnings("TooManyPublicMethods")
 */
class BillingAddressDiffMapperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var BillingAddressDiffMapper
     */
    protected $mapper;

    /**
     * @var OrderAddress|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $billingAddress;

    public function setUp()
    {
        $this->mapper = new BillingAddressDiffMapper();
        $this->billingAddress = $this->getMock('OroB2B\Bundle\OrderBundle\Entity\OrderAddress');
    }

    public function tearDown()
    {
        unset($this->mapper, $this->billingAddress);
    }

    /**
     * @param mixed $billingAddress
     * @return Checkout|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getCheckout($billingAddress)
    {
        $checkout = $this->getMock('OroB2B\Bundle\CheckoutBundle\Entity\Checkout');

        $checkout
            ->expects($this->any())
            ->method('getBillingAddress')
            ->willReturn($billingAddress);

        return $checkout;
    }

    public function testIsEntitySupported()
    {
        $this->assertEquals(
            true,
            $this->mapper->isEntitySupported(
                $this->getCheckout($billingAddress = $this->billingAddress)
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
        $this->assertEquals('billingAddress', $this->mapper->getName());
    }

    public function testGetCurrentState()
    {
        $this->billingAddress
            ->expects($this->once())
            ->method('getId')
            ->willReturn(123);

        $now = new \DateTimeImmutable();

        $this->billingAddress
            ->expects($this->once())
            ->method('getUpdated')
            ->willReturn($now);

        $result = $this->mapper->getCurrentState(
            $this->getCheckout($billingAddress = $this->billingAddress)
        );

        $this->assertEquals(
            [
                'id' => 123,
                'updated' => $now,
            ],
            $result
        );
    }

    public function testGetCurrentStateEmptyBillingAddress()
    {
        $result = $this->mapper->getCurrentState(
            $this->getCheckout($billingAddress = null)
        );

        $this->assertEquals([], $result);
    }

    public function testIsStateActualTrue()
    {
        $this->billingAddress
            ->expects($this->once())
            ->method('getId')
            ->willReturn(123);

        $now = new \DateTimeImmutable();

        $this->billingAddress
            ->expects($this->once())
            ->method('getUpdated')
            ->willReturn($now->modify('-1 minute'));

        $savedState = [
            'parameter1' => 10,
            'billingAddress' => [
                'id' => 123,
                'updated' => $now,
            ],
            'parameter3' => 'green',
        ];

        $result = $this->mapper->isStateActual(
            $this->getCheckout($billingAddress = $this->billingAddress),
            $savedState
        );

        $this->assertEquals(true, $result);
    }

    public function testIsStateActualFalseId()
    {
        $this->billingAddress
            ->expects($this->once())
            ->method('getId')
            ->willReturn(123);

        $now = new \DateTimeImmutable();

        $this->billingAddress
            ->expects($this->never())
            ->method('getUpdated')
            ->willReturn($now->modify('-1 minute'));

        $savedState = [
            'parameter1' => 10,
            'billingAddress' => [
                'id' => 124,
                'updated' => $now,
            ],
            'parameter3' => 'green',
        ];

        $result = $this->mapper->isStateActual(
            $this->getCheckout($billingAddress = $this->billingAddress),
            $savedState
        );

        $this->assertEquals(false, $result);
    }

    public function testIsStateActualFalseUpdated()
    {
        $this->billingAddress
            ->expects($this->once())
            ->method('getId')
            ->willReturn(123);

        $now = new \DateTimeImmutable();

        $this->billingAddress
            ->expects($this->once())
            ->method('getUpdated')
            ->willReturn($now->modify('+1 minute'));

        $savedState = [
            'parameter1' => 10,
            'billingAddress' => [
                'id' => 123,
                'updated' => $now,
            ],
            'parameter3' => 'green',
        ];

        $result = $this->mapper->isStateActual(
            $this->getCheckout($billingAddress = $this->billingAddress),
            $savedState
        );

        $this->assertEquals(false, $result);
    }

    public function testIsStateActualParameterDoesntExist()
    {
        $this->billingAddress
            ->expects($this->never())
            ->method('getId')
            ->willReturn(123);

        $now = new \DateTimeImmutable();

        $this->billingAddress
            ->expects($this->never())
            ->method('getUpdated')
            ->willReturn($now->modify('-1 minute'));

        $savedState = [
            'parameter1' => 10,
            'parameter3' => 'green',
        ];

        $result = $this->mapper->isStateActual(
            $this->getCheckout($billingAddress = $this->billingAddress),
            $savedState
        );

        $this->assertEquals(false, $result);
    }

    public function testIsStateActualParameterOfWrongType()
    {
        $this->billingAddress
            ->expects($this->never())
            ->method('getId')
            ->willReturn(123);

        $now = new \DateTimeImmutable();

        $this->billingAddress
            ->expects($this->never())
            ->method('getUpdated')
            ->willReturn($now->modify('-1 minute'));

        $savedState = [
            'parameter1' => 10,
            'billingAddress' => 1,
            'parameter3' => 'green',
        ];

        $result = $this->mapper->isStateActual(
            $this->getCheckout($billingAddress = $this->billingAddress),
            $savedState
        );

        $this->assertEquals(false, $result);
    }

    public function testIsStateActualParameterOfWrongTypeUpdated()
    {
        $this->billingAddress
            ->expects($this->never())
            ->method('getId')
            ->willReturn(123);

        $now = new \DateTimeImmutable();

        $this->billingAddress
            ->expects($this->never())
            ->method('getUpdated')
            ->willReturn($now->modify('-1 minute'));

        $savedState = [
            'parameter1' => 10,
            'billingAddress' => [
                'id' => 123,
                'updated' => 'yesterday',
            ],
            'parameter3' => 'green',
        ];

        $result = $this->mapper->isStateActual(
            $this->getCheckout($billingAddress = $this->billingAddress),
            $savedState
        );

        $this->assertEquals(false, $result);
    }

    public function testIsStateActualParameterNotSetId()
    {
        $this->billingAddress
            ->expects($this->never())
            ->method('getId')
            ->willReturn(123);

        $now = new \DateTimeImmutable();

        $this->billingAddress
            ->expects($this->never())
            ->method('getUpdated')
            ->willReturn($now->modify('-1 minute'));

        $savedState = [
            'parameter1' => 10,
            'billingAddress' => [
                'updated' => new \DateTimeImmutable(),
            ],
            'parameter3' => 'green',
        ];

        $result = $this->mapper->isStateActual(
            $this->getCheckout($billingAddress = $this->billingAddress),
            $savedState
        );

        $this->assertEquals(false, $result);
    }

    public function testIsStateActualParameterNotSetUpdated()
    {
        $this->billingAddress
            ->expects($this->never())
            ->method('getId')
            ->willReturn(123);

        $now = new \DateTimeImmutable();

        $this->billingAddress
            ->expects($this->never())
            ->method('getUpdated')
            ->willReturn($now->modify('-1 minute'));

        $savedState = [
            'parameter1' => 10,
            'billingAddress' => [
                'id' => 123,
            ],
            'parameter3' => 'green',
        ];

        $result = $this->mapper->isStateActual(
            $this->getCheckout($billingAddress = $this->billingAddress),
            $savedState
        );

        $this->assertEquals(false, $result);
    }
}
