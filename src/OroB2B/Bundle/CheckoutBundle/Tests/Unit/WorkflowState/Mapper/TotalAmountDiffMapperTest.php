<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\WorkflowState\Mapper;

use OroB2B\Bundle\CheckoutBundle\WorkflowState\Mapper\TotalAmountDiffMapper;
use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;

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

    public function testGetPriority()
    {
        $this->assertEquals(50, $this->mapper->getPriority());
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
            ['totalAmount' => [
                'amount' => 1264,
                'currency' => 'EUR',
            ]],
            $result
        );
    }

    public function testCompareStatesTrue()
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

        $result = $this->mapper->compareStates($this->checkout, $savedState);

        $this->assertEquals(true, $result);
    }

    public function testCompareStatesFalseAmount()
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

        $result = $this->mapper->compareStates($this->checkout, $savedState);

        $this->assertEquals(false, $result);
    }

    public function testCompareStatesFalseCurrency()
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

        $result = $this->mapper->compareStates($this->checkout, $savedState);

        $this->assertEquals(false, $result);
    }

    public function testCompareStatesParameterDoesntExist()
    {
        $this->checkout->method('getTotalAmount')->willReturn(true);
        $savedState = [
            'parameter1' => 10,
            'parameter3' => 'green',
        ];

        $result = $this->mapper->compareStates($this->checkout, $savedState);

        $this->assertEquals(false, $result);
    }

    public function testCompareStatesParameterOfWrongType()
    {
        $this->checkout->method('getTotalAmount')->willReturn(true);
        $savedState = [
            'parameter1' => 10,
            'totalAmount' => 1,
            'parameter3' => 'green',
        ];

        $result = $this->mapper->compareStates($this->checkout, $savedState);

        $this->assertEquals(false, $result);
    }

    public function testCompareStatesParameterNotSetAmount()
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

        $result = $this->mapper->compareStates($this->checkout, $savedState);

        $this->assertEquals(false, $result);
    }

    public function testCompareStatesParameterNotSetCurrency()
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

        $result = $this->mapper->compareStates($this->checkout, $savedState);

        $this->assertEquals(false, $result);
    }
}
