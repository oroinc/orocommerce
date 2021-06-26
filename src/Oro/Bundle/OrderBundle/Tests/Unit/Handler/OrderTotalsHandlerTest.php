<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Handler;

use Oro\Bundle\CurrencyBundle\Converter\RateConverterInterface;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Handler\OrderTotalsHandler;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use Symfony\Component\PropertyAccess\PropertyAccess;

class OrderTotalsHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|TotalProcessorProvider */
    private $totalsProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|LineItemSubtotalProvider */
    private $lineItemSubtotalProvider;

    /** @var OrderTotalsHandler */
    private $handler;

    /** @var RateConverterInterface */
    private $rateConverter;

    protected function setUp(): void
    {
        $this->totalsProvider = $this->createMock(TotalProcessorProvider::class);
        $this->lineItemSubtotalProvider = $this->createMock(LineItemSubtotalProvider::class);
        $this->rateConverter = $this->createMock(RateConverterInterface::class);

        $this->handler = new OrderTotalsHandler(
            $this->totalsProvider,
            $this->lineItemSubtotalProvider,
            $this->rateConverter
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
