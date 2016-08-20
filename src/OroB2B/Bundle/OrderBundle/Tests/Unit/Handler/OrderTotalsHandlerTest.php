<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Handler;

use Symfony\Component\PropertyAccess\PropertyAccess;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Handler\OrderTotalsHandler;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;

class OrderTotalsHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TotalProcessorProvider
     */
    protected $totalsProvider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|LineItemSubtotalProvider
     */
    protected $lineItemSubtotalProvider;

    /**
     * @var OrderTotalsHandler
     */
    protected $handler;

    protected function setUp()
    {
        $this->totalsProvider = $this
            ->getMockBuilder('Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->lineItemSubtotalProvider = $this
            ->getMockBuilder('Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->handler = new OrderTotalsHandler(
            $this->totalsProvider,
            $this->lineItemSubtotalProvider
        );
    }

    public function testFillSubtotals()
    {
        $entity = new Order();

        $subtotal = new Subtotal();
        $subtotalAmount = 42;
        $subtotal->setType(LineItemSubtotalProvider::TYPE);
        $subtotal->setAmount($subtotalAmount);

        $total = new Subtotal();
        $totalAmount = 90;
        $total->setType(TotalProcessorProvider::TYPE);
        $total->setAmount($totalAmount);

        $this->lineItemSubtotalProvider->expects($this->any())
            ->method('getSubtotal')
            ->willReturn($subtotal);

        $this->totalsProvider->expects($this->once())
            ->method('enableRecalculation')
            ->willReturnSelf();

        $this->totalsProvider->expects($this->any())
            ->method('getTotal')
            ->willReturn($total);

        $this->handler->fillSubtotals($entity);

        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $this->assertEquals($subtotalAmount, $propertyAccessor->getValue($entity, $subtotal->getType()));
        $this->assertEquals($totalAmount, $propertyAccessor->getValue($entity, $total->getType()));
    }
}
