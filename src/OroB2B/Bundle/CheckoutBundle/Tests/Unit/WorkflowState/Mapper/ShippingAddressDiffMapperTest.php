<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\WorkflowState\Mapper;

use OroB2B\Bundle\AccountBundle\Entity\AccountUserAddress;
use OroB2B\Bundle\CheckoutBundle\WorkflowState\Mapper\ShippingAddressDiffMapper;
use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;
use OroB2B\Bundle\OrderBundle\Entity\OrderAddress;

/**
 * @SuppressWarnings("TooManyPublicMethods")
 */
class ShippingAddressDiffMapperTest extends AbstractCheckoutDiffMapperTest
{
    /**
     * @var OrderAddress|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $shippingAddress;

    protected function setUp()
    {
        parent::setUp();

        $this->mapper = new ShippingAddressDiffMapper();
        $this->shippingAddress = $this->getMock('OroB2B\Bundle\OrderBundle\Entity\OrderAddress');
    }

    protected function tearDown()
    {
        parent::tearDown();

        unset($this->shippingAddress);
    }

    /**
     * @param mixed $shippingAddress
     * @return Checkout|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getCheckout($shippingAddress)
    {
        $this->checkout
            ->expects($this->any())
            ->method('getShippingAddress')
            ->willReturn($shippingAddress);

        return $this->checkout;
    }

    public function testGetName()
    {
        $this->assertEquals('shippingAddress', $this->mapper->getName());
    }

    public function testGetCurrentStateAccountUserAddress()
    {
        $now = new \DateTimeImmutable();
        $accountUserAddress = new AccountUserAddress();
        $accountUserAddress->setId(123);
        $accountUserAddress->setUpdated($now);

        $this->shippingAddress
            ->expects($this->any())
            ->method('getAccountUserAddress')
            ->willReturn($accountUserAddress);

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

    public function testGetCurrentStateTextAddress()
    {
        $this->shippingAddress
            ->expects($this->once())
            ->method('getAccountUserAddress')
            ->willReturn(null);

        $this->shippingAddress
            ->expects($this->once())
            ->method('__toString')
            ->willReturn("First name Last name , Street Street 2 City Kyïvs'ka Oblast' , Ukraine Zip");

        $result = $this->mapper->getCurrentState(
            $this->getCheckout($shippingAddress = $this->shippingAddress)
        );

        $this->assertEquals(
            [
                'text' => "First name Last name , Street Street 2 City Kyïvs'ka Oblast' , Ukraine Zip",
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
        $now = new \DateTimeImmutable();
        $accountUserAddress = new AccountUserAddress();
        $accountUserAddress->setId(123);
        $accountUserAddress->setUpdated($now->modify('-1 minute'));

        $this->shippingAddress
            ->expects($this->any())
            ->method('getAccountUserAddress')
            ->willReturn($accountUserAddress);

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

    public function testIsStateActualTrueTextAddress()
    {
        $this->shippingAddress
            ->expects($this->never())
            ->method('getAccountUserAddress');

        $this->shippingAddress
            ->expects($this->once())
            ->method('__toString')
            ->willReturn("First name Last name , Street Street 2 City Kyïvs'ka Oblast' , Ukraine Zip");

        $savedState = [
            'parameter1' => 10,
            'shippingAddress' => [
                'text' => "First name Last name , Street Street 2 City Kyïvs'ka Oblast' , Ukraine Zip",
            ],
            'parameter3' => 'green',
        ];

        $result = $this->mapper->isStateActual(
            $this->getCheckout($billingAddress = $this->shippingAddress),
            $savedState
        );

        $this->assertEquals(true, $result);
    }

    public function testIsStateActualFalseId()
    {
        $now = new \DateTimeImmutable();
        $accountUserAddress = new AccountUserAddress();
        $accountUserAddress->setId(123);
        $accountUserAddress->setUpdated($now->modify('-1 minute'));

        $this->shippingAddress
            ->expects($this->any())
            ->method('getAccountUserAddress')
            ->willReturn($accountUserAddress);

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
        $now = new \DateTimeImmutable();
        $accountUserAddress = new AccountUserAddress();
        $accountUserAddress->setId(123);
        $accountUserAddress->setUpdated($now->modify('+1 minute'));

        $this->shippingAddress
            ->expects($this->any())
            ->method('getAccountUserAddress')
            ->willReturn($accountUserAddress);

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

    public function testIsStateActualFalseText()
    {
        $this->shippingAddress
            ->expects($this->never())
            ->method('getAccountUserAddress');

        $this->shippingAddress
            ->expects($this->once())
            ->method('__toString')
            ->willReturn("First name Last name , Street Street 2 City Kyïvs'ka Oblast' , Ukraine Zip");

        $savedState = [
            'parameter1' => 10,
            'shippingAddress' => [
                'text' => "First name Last name , Street Street 2 City Pomorskie , Poland Zip",
            ],
            'parameter3' => 'green',
        ];

        $result = $this->mapper->isStateActual(
            $this->getCheckout($billingAddress = $this->shippingAddress),
            $savedState
        );

        $this->assertEquals(false, $result);
    }

    public function testIsStateActualParameterDoesntExist()
    {
        $now = new \DateTimeImmutable();
        $accountUserAddress = new AccountUserAddress();
        $accountUserAddress->setId(123);
        $accountUserAddress->setUpdated($now->modify('-1 minute'));

        $this->shippingAddress
            ->expects($this->any())
            ->method('getAccountUserAddress')
            ->willReturn($accountUserAddress);

        $savedState = [
            'parameter1' => 10,
            'parameter3' => 'green',
        ];

        $result = $this->mapper->isStateActual(
            $this->getCheckout($shippingAddress = $this->shippingAddress),
            $savedState
        );

        $this->assertEquals(true, $result);
    }

    public function testIsStateActualParameterOfWrongType()
    {
        $now = new \DateTimeImmutable();
        $accountUserAddress = new AccountUserAddress();
        $accountUserAddress->setId(123);
        $accountUserAddress->setUpdated($now->modify('-1 minute'));

        $this->shippingAddress
            ->expects($this->any())
            ->method('getAccountUserAddress')
            ->willReturn($accountUserAddress);

        $savedState = [
            'parameter1' => 10,
            'shippingAddress' => 1,
            'parameter3' => 'green',
        ];

        $result = $this->mapper->isStateActual(
            $this->getCheckout($shippingAddress = $this->shippingAddress),
            $savedState
        );

        $this->assertEquals(true, $result);
    }

    public function testIsStateActualParameterOfWrongTypeUpdated()
    {
        $now = new \DateTimeImmutable();
        $accountUserAddress = new AccountUserAddress();
        $accountUserAddress->setId(123);
        $accountUserAddress->setUpdated($now->modify('-1 minute'));

        $this->shippingAddress
            ->expects($this->any())
            ->method('getAccountUserAddress')
            ->willReturn($accountUserAddress);

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

        $this->assertEquals(true, $result);
    }

    public function testIsStateActualParameterNotSetId()
    {
        $now = new \DateTimeImmutable();
        $accountUserAddress = new AccountUserAddress();
        $accountUserAddress->setId(123);
        $accountUserAddress->setUpdated($now->modify('-1 minute'));

        $this->shippingAddress
            ->expects($this->any())
            ->method('getAccountUserAddress')
            ->willReturn($accountUserAddress);

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

        $this->assertEquals(true, $result);
    }

    public function testIsStateActualParameterNotSetUpdated()
    {
        $now = new \DateTimeImmutable();
        $accountUserAddress = new AccountUserAddress();
        $accountUserAddress->setId(123);
        $accountUserAddress->setUpdated($now->modify('-1 minute'));

        $this->shippingAddress
            ->expects($this->any())
            ->method('getAccountUserAddress')
            ->willReturn($accountUserAddress);

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

        $this->assertEquals(true, $result);
    }
}
