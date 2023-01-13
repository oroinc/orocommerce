<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Total;

use Oro\Bundle\CurrencyBundle\Converter\RateConverterInterface;
use Oro\Bundle\CurrencyBundle\Entity\MultiCurrency;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Provider\DiscountSubtotalProvider;
use Oro\Bundle\OrderBundle\Total\TotalHelper;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;

class TotalHelperTest extends \PHPUnit\Framework\TestCase
{
    /** @var TotalProcessorProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $totalProvider;

    /** @var LineItemSubtotalProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $lineItemSubtotalProvider;

    /** @var DiscountSubtotalProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $discountSubtotalProvider;

    /** @var RateConverterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $rateConverter;

    /** @var TotalHelper */
    private $helper;

    protected function setUp(): void
    {
        $this->totalProvider = $this->createMock(TotalProcessorProvider::class);
        $this->lineItemSubtotalProvider = $this->createMock(LineItemSubtotalProvider::class);
        $this->discountSubtotalProvider = $this->createMock(DiscountSubtotalProvider::class);
        $this->rateConverter = $this->createMock(RateConverterInterface::class);

        $this->helper = new TotalHelper(
            $this->totalProvider,
            $this->lineItemSubtotalProvider,
            $this->discountSubtotalProvider,
            $this->rateConverter
        );
    }

    public function testFillSubtotals()
    {
        $subtotal = new Subtotal();
        $subtotalAmount = 42;
        $subtotal->setType(LineItemSubtotalProvider::TYPE);
        $subtotal->setAmount($subtotalAmount);

        $this->lineItemSubtotalProvider->expects($this->any())
            ->method('getSubtotal')
            ->willReturn($subtotal);

        $this->rateConverter
            ->expects($this->once())
            ->method('getBaseCurrencyAmount')
            ->willReturnCallback(function (MultiCurrency $multiCurrency) {
                return $multiCurrency->getValue();
            });

        $order = new Order();
        $this->helper->fillSubtotals($order);

        $this->assertEquals(42, $order->getSubtotal());
    }

    public function testFillDiscounts()
    {
        $discountSubtotal = new Subtotal();
        $discountSubtotalAmount = 42;
        $discountSubtotal->setType(DiscountSubtotalProvider::TYPE);
        $discountSubtotal->setAmount($discountSubtotalAmount);

        $discountSubtotal2 = new Subtotal();
        $discountSubtotalAmount2 = -40;
        $discountSubtotal2->setType(DiscountSubtotalProvider::TYPE);
        $discountSubtotal2->setAmount($discountSubtotalAmount2);

        $this->discountSubtotalProvider->expects($this->any())
            ->method('getSubtotal')
            ->willReturn([$discountSubtotal, $discountSubtotal2]);

        $order = new Order();
        $this->helper->fillDiscounts($order);
        $this->assertEquals(2, $order->getTotalDiscounts()->getValue());
    }

    public function testFillTotal()
    {
        $order = new Order();

        $total = new Subtotal();
        $totalAmount = 90;
        $total->setType(TotalProcessorProvider::TYPE);
        $total->setAmount($totalAmount);

        $this->totalProvider->expects($this->any())
            ->method('enableRecalculation')
            ->willReturnSelf();

        $this->totalProvider->expects($this->any())
            ->method('getTotal')
            ->with($order)
            ->willReturn($total);

        $this->rateConverter->expects($this->once())
            ->method('getBaseCurrencyAmount')
            ->willReturnCallback(function (MultiCurrency $multiCurrency) {
                return $multiCurrency->getValue();
            });

        $this->helper->fillTotal($order);

        $this->assertEquals($totalAmount, $order->getTotal());
    }
}
