<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\WorkflowState\Mapper;

use OroB2B\Bundle\AccountBundle\Entity\AccountUserAddress;
use OroB2B\Bundle\CheckoutBundle\WorkflowState\Mapper\BillingAddressDiffMapper;
use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;
use OroB2B\Bundle\OrderBundle\Entity\OrderAddress;

/**
 * @SuppressWarnings("TooManyPublicMethods")
 */
class BillingAddressDiffMapperTest extends AbstractCheckoutDiffMapperTest
{
    /**
     * @var OrderAddress|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $billingAddress;

    protected function setUp()
    {
        parent::setUp();

        $this->mapper = new BillingAddressDiffMapper();
        $this->billingAddress = $this->getMock('OroB2B\Bundle\OrderBundle\Entity\OrderAddress');
    }

    protected function tearDown()
    {
        parent::tearDown();

        unset($this->billingAddress);
    }

    /**
     * @param mixed $billingAddress
     * @return Checkout|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getCheckout($billingAddress)
    {
        $this->checkout
            ->expects($this->any())
            ->method('getBillingAddress')
            ->willReturn($billingAddress);

        return $this->checkout;
    }

    public function testGetName()
    {
        $this->assertEquals('billingAddress', $this->mapper->getName());
    }

    public function testGetCurrentStateAccountUserAddress()
    {
        $now = new \DateTimeImmutable();
        $accountUserAddress = new AccountUserAddress();
        $accountUserAddress->setId(123);
        $accountUserAddress->setUpdated($now);

        $this->billingAddress
            ->expects($this->any())
            ->method('getAccountUserAddress')
            ->willReturn($accountUserAddress);

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

    public function testGetCurrentStateTextAddress()
    {
        $this->billingAddress
            ->expects($this->once())
            ->method('getAccountUserAddress')
            ->willReturn(null);

        $this->billingAddress
            ->expects($this->once())
            ->method('__toString')
            ->willReturn("First name Last name , Street Street 2 City Kyïvs'ka Oblast' , Ukraine Zip");

        $result = $this->mapper->getCurrentState(
            $this->getCheckout($billingAddress = $this->billingAddress)
        );

        $this->assertEquals(
            [
                'text' => "First name Last name , Street Street 2 City Kyïvs'ka Oblast' , Ukraine Zip",
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

    public function testIsStateActualTrueAccountUserAddress()
    {
        $now = new \DateTimeImmutable();
        $accountUserAddress = new AccountUserAddress();
        $accountUserAddress->setId(123);
        $accountUserAddress->setUpdated($now->modify('-1 minute'));

        $this->billingAddress
            ->expects($this->any())
            ->method('getAccountUserAddress')
            ->willReturn($accountUserAddress);

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

    public function testIsStateActualTrueTextAddress()
    {
        $this->billingAddress
            ->expects($this->never())
            ->method('getAccountUserAddress');

        $this->billingAddress
            ->expects($this->once())
            ->method('__toString')
            ->willReturn("First name Last name , Street Street 2 City Kyïvs'ka Oblast' , Ukraine Zip");

        $savedState = [
            'parameter1' => 10,
            'billingAddress' => [
                'text' => "First name Last name , Street Street 2 City Kyïvs'ka Oblast' , Ukraine Zip",
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
        $now = new \DateTimeImmutable();
        $accountUserAddress = new AccountUserAddress();
        $accountUserAddress->setId(123);
        $accountUserAddress->setUpdated($now->modify('-1 minute'));

        $this->billingAddress
            ->expects($this->any())
            ->method('getAccountUserAddress')
            ->willReturn($accountUserAddress);

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
        $now = new \DateTimeImmutable();
        $accountUserAddress = new AccountUserAddress();
        $accountUserAddress->setId(123);
        $accountUserAddress->setUpdated($now->modify('+1 minute'));

        $this->billingAddress
            ->expects($this->any())
            ->method('getAccountUserAddress')
            ->willReturn($accountUserAddress);

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

    public function testIsStateActualFalseText()
    {
        $this->billingAddress
            ->expects($this->never())
            ->method('getAccountUserAddress');

        $this->billingAddress
            ->expects($this->once())
            ->method('__toString')
            ->willReturn("First name Last name , Street Street 2 City Kyïvs'ka Oblast' , Ukraine Zip");

        $savedState = [
            'parameter1' => 10,
            'billingAddress' => [
                'text' => "First name Last name , Street Street 2 City Pomorskie , Poland Zip",
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
        $now = new \DateTimeImmutable();
        $accountUserAddress = new AccountUserAddress();
        $accountUserAddress->setId(123);
        $accountUserAddress->setUpdated($now->modify('-1 minute'));

        $this->billingAddress
            ->expects($this->any())
            ->method('getAccountUserAddress')
            ->willReturn($accountUserAddress);

        $savedState = [
            'parameter1' => 10,
            'parameter3' => 'green',
        ];

        $result = $this->mapper->isStateActual(
            $this->getCheckout($billingAddress = $this->billingAddress),
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

        $this->billingAddress
            ->expects($this->any())
            ->method('getAccountUserAddress')
            ->willReturn($accountUserAddress);

        $savedState = [
            'parameter1' => 10,
            'billingAddress' => 1,
            'parameter3' => 'green',
        ];

        $result = $this->mapper->isStateActual(
            $this->getCheckout($billingAddress = $this->billingAddress),
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

        $this->billingAddress
            ->expects($this->any())
            ->method('getAccountUserAddress')
            ->willReturn($accountUserAddress);

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

        $this->assertEquals(true, $result);
    }

    public function testIsStateActualParameterNotSetId()
    {
        $now = new \DateTimeImmutable();
        $accountUserAddress = new AccountUserAddress();
        $accountUserAddress->setId(123);
        $accountUserAddress->setUpdated($now->modify('-1 minute'));

        $this->billingAddress
            ->expects($this->any())
            ->method('getAccountUserAddress')
            ->willReturn($accountUserAddress);

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

        $this->assertEquals(true, $result);
    }

    public function testIsStateActualParameterNotSetUpdated()
    {
        $now = new \DateTimeImmutable();
        $accountUserAddress = new AccountUserAddress();
        $accountUserAddress->setId(123);
        $accountUserAddress->setUpdated($now->modify('-1 minute'));

        $this->billingAddress
            ->expects($this->any())
            ->method('getAccountUserAddress')
            ->willReturn($accountUserAddress);

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

        $this->assertEquals(true, $result);
    }
}
