<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\WorkflowState\Mapper;

use OroB2B\Bundle\CheckoutBundle\WorkflowState\Mapper\BillingAddressDiffMapper;
use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;
use OroB2B\Bundle\OrderBundle\Entity\OrderAddress;

class BillingAddressDiffMapperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var BillingAddressDiffMapper
     */
    private $mapper;

    /**
     * @var Checkout|\PHPUnit_Framework_MockObject_MockObject
     */
    private $checkout;

    /**
     * @var OrderAddress|\PHPUnit_Framework_MockObject_MockObject
     */
    private $billingAddress;

    public function setUp()
    {
        $this->mapper = new BillingAddressDiffMapper();
        $this->checkout = $this->getMock('OroB2B\Bundle\CheckoutBundle\Entity\Checkout');
        $this->billingAddress = $this->getMock('OroB2B\Bundle\OrderBundle\Entity\OrderAddress');
        $this->checkout->method('getBillingAddress')->willReturn($this->billingAddress);
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
        $this->billingAddress->method('getId')->willReturn(123);
        $now = new \DateTimeImmutable();
        $this->billingAddress->method('getUpdated')->willReturn($now);

        $result = $this->mapper->getCurrentState($this->checkout);

        $this->assertEquals(
            ['billingAddress' => [
                'id' => 123,
                'updated' => $now,
            ]],
            $result
        );
    }

    public function testCompareStatesTrue()
    {
        $this->billingAddress->method('getId')->willReturn(123);
        $now = new \DateTimeImmutable();
        $this->billingAddress->method('getUpdated')->willReturn($now->modify('-1 minute'));

        $savedState = [
            'parameter1' => 10,
            'billingAddress' => [
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
        $this->billingAddress->method('getId')->willReturn(123);
        $now = new \DateTimeImmutable();
        $this->billingAddress->method('getUpdated')->willReturn($now->modify('-1 minute'));

        $savedState = [
            'parameter1' => 10,
            'billingAddress' => [
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
        $this->billingAddress->method('getId')->willReturn(123);
        $now = new \DateTimeImmutable();
        $this->billingAddress->method('getUpdated')->willReturn($now->modify('+1 minute'));

        $savedState = [
            'parameter1' => 10,
            'billingAddress' => [
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
        $this->billingAddress->method('getId')->willReturn(123);
        $now = new \DateTimeImmutable();
        $this->billingAddress->method('getUpdated')->willReturn($now->modify('-1 minute'));

        $savedState = [
            'parameter1' => 10,
            'parameter3' => 'green',
        ];

        $result = $this->mapper->compareStates($this->checkout, $savedState);

        $this->assertEquals(false, $result);
    }

    public function testCompareStatesParameterOfWrongType()
    {
        $this->billingAddress->method('getId')->willReturn(123);
        $now = new \DateTimeImmutable();
        $this->billingAddress->method('getUpdated')->willReturn($now->modify('-1 minute'));

        $savedState = [
            'parameter1' => 10,
            'billingAddress' => 1,
            'parameter3' => 'green',
        ];

        $result = $this->mapper->compareStates($this->checkout, $savedState);

        $this->assertEquals(false, $result);
    }

    public function testCompareStatesParameterOfWrongTypeUpdated()
    {
        $this->billingAddress->method('getId')->willReturn(123);
        $now = new \DateTimeImmutable();
        $this->billingAddress->method('getUpdated')->willReturn($now->modify('-1 minute'));

        $savedState = [
            'parameter1' => 10,
            'billingAddress' => [
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
        $this->billingAddress->method('getId')->willReturn(123);
        $now = new \DateTimeImmutable();
        $this->billingAddress->method('getUpdated')->willReturn($now->modify('-1 minute'));

        $savedState = [
            'parameter1' => 10,
            'billingAddress' => [
                'updated' => new \DateTimeImmutable(),
            ],
            'parameter3' => 'green',
        ];

        $result = $this->mapper->compareStates($this->checkout, $savedState);

        $this->assertEquals(false, $result);
    }

    public function testCompareStatesParameterNotSetUpdated()
    {
        $this->billingAddress->method('getId')->willReturn(123);
        $now = new \DateTimeImmutable();
        $this->billingAddress->method('getUpdated')->willReturn($now->modify('-1 minute'));

        $savedState = [
            'parameter1' => 10,
            'billingAddress' => [
                'id' => 123,
            ],
            'parameter3' => 'green',
        ];

        $result = $this->mapper->compareStates($this->checkout, $savedState);

        $this->assertEquals(false, $result);
    }
}
