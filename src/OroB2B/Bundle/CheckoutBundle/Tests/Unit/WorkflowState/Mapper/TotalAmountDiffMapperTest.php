<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\WorkflowState\Mapper;

use OroB2B\Bundle\CheckoutBundle\WorkflowState\Mapper\TotalAmountDiffMapper;
use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;

/**
 * @SuppressWarnings("TooManyPublicMethods")
 */
class TotalAmountDiffMapperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TotalAmountDiffMapper
     */
    private $mapper;

    /**
     * @var Checkout|\PHPUnit_Framework_MockObject_MockObject
     */
    private $checkout;

    /**
     * @var TotalProcessorProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    private $totalProcessorProvider;

    public function setUp()
    {
        $this->totalProcessorProvider = $this->getMockBuilder(
            'OroB2B\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider'
        )
            ->disableOriginalConstructor()
            ->getMock();
        $this->mapper = new TotalAmountDiffMapper($this->totalProcessorProvider);
        $this->checkout = $this->getMock('OroB2B\Bundle\CheckoutBundle\Entity\Checkout');
    }

    public function tearDown()
    {
        unset($this->mapper, $this->checkout, $this->totalProcessorProvider);
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
        $this->assertEquals('totalAmount', $this->mapper->getName());
    }

    public function testGetCurrentState()
    {
        $total = new Subtotal();
        $total->setAmount(1264);
        $total->setCurrency('EUR');
        $this->totalProcessorProvider
            ->expects($this->once())
            ->method('getTotal')
            ->with($this->checkout)
            ->willReturn($total);

        $result = $this->mapper->getCurrentState($this->checkout);

        $this->assertEquals(
            [
                'amount' => 1264,
                'currency' => 'EUR',
            ],
            $result
        );
    }

    public function testIsStateActualTrue()
    {
        $total = new Subtotal();
        $total->setAmount(1264);
        $total->setCurrency('EUR');
        $this->totalProcessorProvider
            ->expects($this->once())
            ->method('getTotal')
            ->with($this->checkout)
            ->willReturn($total);

        $savedState = [
            'parameter1' => 10,
            'totalAmount' => [
                'amount' => 1264,
                'currency' => 'EUR',
            ],
            'parameter3' => 'green',
        ];

        $result = $this->mapper->isStateActual($this->checkout, $savedState);

        $this->assertEquals(true, $result);
    }

    public function testIsStateActualFalseAmount()
    {
        $total = new Subtotal();
        $total->setAmount(1264);
        $total->setCurrency('EUR');
        $this->totalProcessorProvider
            ->expects($this->once())
            ->method('getTotal')
            ->with($this->checkout)
            ->willReturn($total);

        $savedState = [
            'parameter1' => 10,
            'totalAmount' => [
                'amount' => 6407,
                'currency' => 'EUR',
            ],
            'parameter3' => 'green',
        ];

        $result = $this->mapper->isStateActual($this->checkout, $savedState);

        $this->assertEquals(false, $result);
    }

    public function testIsStateActualFalseCurrency()
    {
        $total = new Subtotal();
        $total->setAmount(1264);
        $total->setCurrency('EUR');
        $this->totalProcessorProvider
            ->expects($this->once())
            ->method('getTotal')
            ->with($this->checkout)
            ->willReturn($total);

        $savedState = [
            'parameter1' => 10,
            'totalAmount' => [
                'amount' => 1264,
                'currency' => 'CAD',
            ],
            'parameter3' => 'green',
        ];

        $result = $this->mapper->isStateActual($this->checkout, $savedState);

        $this->assertEquals(false, $result);
    }

    public function testIsStateActualParameterDoesntExist()
    {
        $this->checkout->method('getTotalAmount')->willReturn(true);
        $savedState = [
            'parameter1' => 10,
            'parameter3' => 'green',
        ];

        $result = $this->mapper->isStateActual($this->checkout, $savedState);

        $this->assertEquals(false, $result);
    }

    public function testIsStateActualParameterOfWrongType()
    {
        $this->checkout->method('getTotalAmount')->willReturn(true);
        $savedState = [
            'parameter1' => 10,
            'totalAmount' => 1,
            'parameter3' => 'green',
        ];

        $result = $this->mapper->isStateActual($this->checkout, $savedState);

        $this->assertEquals(false, $result);
    }

    public function testIsStateActualParameterNotSetAmount()
    {
        $total = new Subtotal();
        $total->setAmount(1264);
        $total->setCurrency('EUR');
        $this->totalProcessorProvider
            ->expects($this->once())
            ->method('getTotal')
            ->with($this->checkout)
            ->willReturn($total);

        $savedState = [
            'parameter1' => 10,
            'totalAmount' => [
                'currency' => 'EUR',
            ],
            'parameter3' => 'green',
        ];

        $result = $this->mapper->isStateActual($this->checkout, $savedState);

        $this->assertEquals(false, $result);
    }

    public function testIsStateActualParameterNotSetCurrency()
    {
        $total = new Subtotal();
        $total->setAmount(1264);
        $total->setCurrency('EUR');
        $this->totalProcessorProvider
            ->expects($this->once())
            ->method('getTotal')
            ->with($this->checkout)
            ->willReturn($total);

        $savedState = [
            'parameter1' => 10,
            'totalAmount' => [
                'amount' => 1264,
            ],
            'parameter3' => 'green',
        ];

        $result = $this->mapper->isStateActual($this->checkout, $savedState);

        $this->assertEquals(false, $result);
    }
}
