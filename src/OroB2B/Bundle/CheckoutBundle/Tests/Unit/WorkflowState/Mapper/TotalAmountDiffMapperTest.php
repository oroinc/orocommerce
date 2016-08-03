<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\WorkflowState\Mapper;

use OroB2B\Bundle\CheckoutBundle\WorkflowState\Mapper\TotalAmountDiffMapper;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;

class TotalAmountDiffMapperTest extends AbstractCheckoutDiffMapperTest
{
    /**
     * @var TotalProcessorProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $totalProcessorProvider;

    public function setUp()
    {
        parent::setUp();

        $this->totalProcessorProvider = $this->getMockBuilder(
            'OroB2B\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider'
        )
            ->disableOriginalConstructor()
            ->getMock();
        $this->mapper = new TotalAmountDiffMapper($this->totalProcessorProvider);
    }

    public function tearDown()
    {
        parent::tearDown();

        unset($this->totalProcessorProvider);
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
            ->expects($this->exactly(2))
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
            ->expects($this->exactly(2))
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
        $this->checkout
            ->expects($this->never())
            ->method('getTotalAmount');

        $savedState = [
            'parameter1' => 10,
            'parameter3' => 'green',
        ];

        $result = $this->mapper->isStateActual($this->checkout, $savedState);

        $this->assertEquals(true, $result);
    }

    public function testIsStateActualParameterOfWrongType()
    {
        $this->checkout
            ->expects($this->never())
            ->method('getTotalAmount');

        $savedState = [
            'parameter1' => 10,
            'totalAmount' => 1,
            'parameter3' => 'green',
        ];

        $result = $this->mapper->isStateActual($this->checkout, $savedState);

        $this->assertEquals(true, $result);
    }

    public function testIsStateActualParameterNotSetAmount()
    {
        $total = new Subtotal();
        $total->setAmount(1264);
        $total->setCurrency('EUR');

        $this->totalProcessorProvider
            ->expects($this->never())
            ->method('getTotal');

        $savedState = [
            'parameter1' => 10,
            'totalAmount' => [
                'currency' => 'EUR',
            ],
            'parameter3' => 'green',
        ];

        $result = $this->mapper->isStateActual($this->checkout, $savedState);

        $this->assertEquals(true, $result);
    }

    public function testIsStateActualParameterNotSetCurrency()
    {
        $total = new Subtotal();
        $total->setAmount(1264);
        $total->setCurrency('EUR');

        $this->totalProcessorProvider
            ->expects($this->never())
            ->method('getTotal');

        $savedState = [
            'parameter1' => 10,
            'totalAmount' => [
                'amount' => 1264,
            ],
            'parameter3' => 'green',
        ];

        $result = $this->mapper->isStateActual($this->checkout, $savedState);

        $this->assertEquals(true, $result);
    }
}
