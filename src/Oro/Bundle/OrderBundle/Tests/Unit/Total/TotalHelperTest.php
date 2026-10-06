<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Total;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CurrencyBundle\Converter\RateConverterInterface;
use Oro\Bundle\CurrencyBundle\Entity\MultiCurrency;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderDiscount;
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

    #[\Override]
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

    public function testFill(): void
    {
        $order = new Order();
        $discount = new OrderDiscount();
        $discount->setType(OrderDiscount::TYPE_AMOUNT);
        $discount->setAmount(4.2);
        $order->addDiscount($discount);

        $lineItemsSubtotal = new Subtotal();
        $lineItemsSubtotal->setType(LineItemSubtotalProvider::TYPE);
        $lineItemsSubtotal->setName(LineItemSubtotalProvider::NAME);
        $lineItemsSubtotal->setAmount(42);

        $discountSubtotal = new Subtotal();
        $discountSubtotal->setType(DiscountSubtotalProvider::TYPE);
        $discountSubtotal->setName(DiscountSubtotalProvider::NAME);
        $discountSubtotal->setAmount(42);

        $discountSubtotal2 = new Subtotal();
        $discountSubtotal2->setType(DiscountSubtotalProvider::TYPE);
        $discountSubtotal2->setName(DiscountSubtotalProvider::NAME);
        $discountSubtotal2->setAmount(-40);

        $total = new Subtotal();
        $total->setType(TotalProcessorProvider::TYPE);
        $total->setAmount(90);

        $subtotals = new ArrayCollection([$lineItemsSubtotal, $discountSubtotal, $discountSubtotal2]);

        $this->totalProvider->expects($this->once())
            ->method('enableRecalculation')
            ->willReturnSelf();
        // The whole subtotal-provider chain must be calculated only once per fill().
        $this->totalProvider->expects($this->once())
            ->method('getSubtotals')
            ->with($order)
            ->willReturn($subtotals);
        $this->totalProvider->expects($this->once())
            ->method('getTotalForSubtotals')
            ->with($order, $subtotals)
            ->willReturn($total);
        // Dedicated providers must not be called again - values are reused from getSubtotals().
        $this->lineItemSubtotalProvider->expects($this->never())
            ->method('getSubtotal');
        $this->discountSubtotalProvider->expects($this->never())
            ->method('getSubtotal');

        $this->rateConverter->expects($this->any())
            ->method('getBaseCurrencyAmount')
            ->willReturnCallback(function (MultiCurrency $multiCurrency) {
                return $multiCurrency->getValue();
            });

        $this->helper->fill($order);

        $this->assertEquals(42, $order->getSubtotal());
        $this->assertEquals(2, $order->getTotalDiscounts()->getValue());
        $this->assertEquals(90, $order->getTotal());
        // Percent of an amount-type discount is derived from the line items subtotal.
        $this->assertEquals(10.0, $order->getDiscounts()[0]->getPercent());
    }

    public function testFillForOrderWithSuborders(): void
    {
        $order = new Order();
        $subOrder1 = new Order();
        $subOrder1->setSubtotal(42);
        $subOrder1->setTotal(50);
        $subOrder1->setTotalDiscounts((new Price())->setValue(3));
        $subOrder2 = new Order();
        $subOrder2->setSubtotal(55);
        $subOrder2->setTotal(60);
        $subOrder2->setTotalDiscounts((new Price())->setValue(4));

        $order->addSubOrder($subOrder1);
        $order->addSubOrder($subOrder2);

        // Sub-order aggregation must not trigger the provider chain.
        $this->totalProvider->expects($this->never())
            ->method('getSubtotals');
        $this->totalProvider->expects($this->never())
            ->method('getTotalForSubtotals');

        $this->rateConverter->expects($this->any())
            ->method('getBaseCurrencyAmount')
            ->willReturnCallback(function (MultiCurrency $multiCurrency) {
                return $multiCurrency->getValue();
            });

        $this->helper->fill($order);

        $this->assertEquals(97, $order->getSubtotal());
        $this->assertEquals(7, $order->getTotalDiscounts()->getValue());
        $this->assertEquals(110, $order->getTotal());
    }

    public function testFillSubtotals(): void
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

    public function testFillSubtotalsForOrderWithSuborders(): void
    {
        $order = new Order();
        $subOrder1 = new Order();
        $subOrder1->setSubtotal(42);
        $subOrder2 = new Order();
        $subOrder2->setSubtotal(55);

        $order->addSubOrder($subOrder1);
        $order->addSubOrder($subOrder2);

        $this->helper->fillSubtotals($order);

        $this->assertEquals(97, $order->getSubtotal());
    }

    public function testFillDiscounts(): void
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

    public function testFillDiscountsWithSuborders(): void
    {
        $order = new Order();
        $discount1 = new Price();
        $discount1->setValue(89);
        $subOrder1 = new Order();
        $subOrder1->setTotalDiscounts($discount1);
        $discount2 = new Price();
        $discount2->setValue(10);
        $subOrder2 = new Order();
        $subOrder2->setTotalDiscounts($discount2);

        $order->addSubOrder($subOrder1);
        $order->addSubOrder($subOrder2);

        $this->helper->fillDiscounts($order);

        $this->assertEquals(99, $order->getTotalDiscounts()->getValue());
    }

    public function testFillTotal(): void
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

    public function testCalculateTotal(): void
    {
        $order = new Order();

        $total = new Subtotal();
        $totalAmount = 100.00;
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

        $this->assertEquals($totalAmount, $this->helper->calculateTotal($order)->getValue());
    }

    public function testFillTotalForOrderWithSuborders(): void
    {
        $order = new Order();
        $subOrder1 = new Order();
        $subOrder1->setTotal(32);
        $subOrder2 = new Order();
        $subOrder2->setTotal(33);

        $order->addSubOrder($subOrder1);
        $order->addSubOrder($subOrder2);

        $this->helper->fillTotal($order);

        $this->assertEquals(65, $order->getTotal());
    }
}
