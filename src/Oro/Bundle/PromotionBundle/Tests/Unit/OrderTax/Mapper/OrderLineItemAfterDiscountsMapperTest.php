<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\OrderTax\Mapper;

use Brick\Math\BigDecimal;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PromotionBundle\Discount\DiscountContext;
use Oro\Bundle\PromotionBundle\Discount\DiscountLineItem;
use Oro\Bundle\PromotionBundle\Executor\PromotionExecutor;
use Oro\Bundle\PromotionBundle\OrderTax\Mapper\OrderLineItemAfterDiscountsMapper;
use Oro\Bundle\TaxBundle\Mapper\TaxMapperInterface;
use Oro\Bundle\TaxBundle\Model\Taxable;
use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OrderLineItemAfterDiscountsMapperTest extends TestCase
{
    private TaxMapperInterface|MockObject $innerMapper;
    private TaxationSettingsProvider|MockObject $taxationSettingsProvider;
    private PromotionExecutor|MockObject $promotionExecutor;
    private OrderLineItemAfterDiscountsMapper $orderLineItemAfterDiscountsMapper;

    protected function setUp(): void
    {
        $this->innerMapper = $this->createMock(TaxMapperInterface::class);
        $this->taxationSettingsProvider = $this->createMock(TaxationSettingsProvider::class);
        $this->promotionExecutor = $this->createMock(PromotionExecutor::class);

        $this->orderLineItemAfterDiscountsMapper = new OrderLineItemAfterDiscountsMapper(
            $this->innerMapper,
            $this->taxationSettingsProvider,
            $this->promotionExecutor
        );
    }

    public function testMapCachedDiscountContext(): void
    {
        $taxable = new Taxable();
        $order = new Order();
        $price = new Price();
        $lineItem = new OrderLineItem();
        $lineItem
            ->setPrice($price)
            ->setOrder($order);

        $this->innerMapper
            ->expects(self::exactly(2))
            ->method('map')
            ->with($lineItem)
            ->willReturn($taxable);

        $this->taxationSettingsProvider
            ->expects(self::exactly(2))
            ->method('isCalculateAfterPromotionsEnabled')
            ->willReturn(true);

        $discountLineItem = new DiscountLineItem();
        $discountLineItem->setSourceLineItem($lineItem);
        $discountContext = new DiscountContext();
        $discountContext->setLineItems([$discountLineItem]);

        $this->promotionExecutor
            ->expects(self::exactly(2))
            ->method('supports')
            ->with($order)
            ->willReturn(true);
        $this->promotionExecutor
            ->expects(self::once())
            ->method('execute')
            ->with($order)
            ->willReturn($discountContext);

        $this->orderLineItemAfterDiscountsMapper->map($lineItem);
        // Make sure that promotionExecutor->execute() does not call more than 2 times.
        $this->orderLineItemAfterDiscountsMapper->map($lineItem);
    }

    public function testMapLineItemNoPrice(): void
    {
        $taxable = new Taxable();
        $taxable->setAmount(4);
        $taxable->setShippingCost(2);

        $lineItem = new OrderLineItem();
        $this->innerMapper->expects(self::once())
            ->method('map')
            ->with($lineItem)
            ->willReturn($taxable);

        $this->taxationSettingsProvider->expects(self::never())
            ->method('isCalculateAfterPromotionsEnabled');

        $this->promotionExecutor->expects(self::never())
            ->method('supports')
            ->withAnyParameters();
        $this->promotionExecutor->expects(self::never())
            ->method('execute')
            ->withAnyParameters();

        self::assertEquals(clone $taxable, $this->orderLineItemAfterDiscountsMapper->map($lineItem));
    }

    public function testMapCalculateAfterPromotionsDisabled(): void
    {
        $taxable = new Taxable();
        $taxable->setPrice(4);

        $price = new Price();
        $lineItem = new OrderLineItem();
        $lineItem->setPrice($price);
        $this->innerMapper->expects(self::once())
            ->method('map')
            ->with($lineItem)
            ->willReturn($taxable);

        $this->taxationSettingsProvider->expects(self::once())
            ->method('isCalculateAfterPromotionsEnabled')
            ->willReturn(false);

        $this->promotionExecutor->expects(self::never())
            ->method('supports')
            ->withAnyParameters();
        $this->promotionExecutor->expects(self::never())
            ->method('execute')
            ->withAnyParameters();

        self::assertEquals(clone $taxable, $this->orderLineItemAfterDiscountsMapper->map($lineItem));
    }

    public function testMapExecutorNotSupportedEntity(): void
    {
        $taxable = new Taxable();
        $taxable->setPrice(4);

        $order = new Order();
        $price = new Price();
        $lineItem = new OrderLineItem();
        $lineItem
            ->setPrice($price)
            ->setOrder($order);
        $this->innerMapper->expects(self::once())
            ->method('map')
            ->with($lineItem)
            ->willReturn($taxable);

        $this->taxationSettingsProvider->expects(self::once())
            ->method('isCalculateAfterPromotionsEnabled')
            ->willReturn(true);

        $this->promotionExecutor->expects(self::once())
            ->method('supports')
            ->with($order)
            ->willReturn(false);
        $this->promotionExecutor->expects(self::never())
            ->method('execute')
            ->withAnyParameters();

        self::assertEquals(clone $taxable, $this->orderLineItemAfterDiscountsMapper->map($lineItem));
    }

    public function testMapNoLineItems(): void
    {
        $taxable = new Taxable();
        $taxable->setPrice(4);

        $order = new Order();
        $price = new Price();
        $lineItem = new OrderLineItem();
        $lineItem
            ->setPrice($price)
            ->setOrder($order);
        $this->innerMapper->expects(self::once())
            ->method('map')
            ->with($lineItem)
            ->willReturn($taxable);

        $this->taxationSettingsProvider->expects(self::once())
            ->method('isCalculateAfterPromotionsEnabled')
            ->willReturn(true);

        $discountContext = new DiscountContext();
        $this->promotionExecutor->expects(self::once())
            ->method('supports')
            ->with($order)
            ->willReturn(true);
        $this->promotionExecutor->expects(self::once())
            ->method('execute')
            ->with($order)
            ->willReturn($discountContext);

        self::assertEquals(clone $taxable, $this->orderLineItemAfterDiscountsMapper->map($lineItem));
    }

    public function testMapWrongSourceLineItem(): void
    {
        $taxable = new Taxable();
        $taxable->setPrice(4);

        $order = new Order();
        $price = new Price();
        $lineItem = new OrderLineItem();
        $lineItem
            ->setPrice($price)
            ->setOrder($order)
            ->setProductSku('item')
            ->setQuantity(1)
            ->setProductUnitCode('item')
            ->setValue(100);
        $this->innerMapper->expects(self::once())
            ->method('map')
            ->with($lineItem)
            ->willReturn($taxable);

        $lineItem1 = new OrderLineItem();
        $lineItem1
            ->setPrice($price)
            ->setOrder($order)
            ->setProductSku('item')
            ->setQuantity(2)
            ->setProductUnitCode('item')
            ->setValue(100);

        $this->taxationSettingsProvider->expects(self::once())
            ->method('isCalculateAfterPromotionsEnabled')
            ->willReturn(true);

        $discountLineItem = new DiscountLineItem();
        $discountLineItem->setSourceLineItem($lineItem1);
        $discountContext = new DiscountContext();
        $discountContext->setLineItems([$discountLineItem]);

        $this->promotionExecutor->expects(self::once())
            ->method('supports')
            ->with($order)
            ->willReturn(true);
        $this->promotionExecutor->expects(self::once())
            ->method('execute')
            ->with($order)
            ->willReturn($discountContext);

        self::assertEquals(clone $taxable, $this->orderLineItemAfterDiscountsMapper->map($lineItem));
    }

    /**
     * @dataProvider getMapProvider
     */
    public function testMap(
        float $lineItemSubtotal,
        float $lineItemSubtotalAfterDiscounts,
        float $expectedTaxablePrice
    ): void {
        $taxable = new Taxable();
        $taxable->setPrice(4);

        $order = new Order();
        $price = new Price();
        $lineItem = new OrderLineItem();
        $lineItem
            ->setPrice($price)
            ->setOrder($order);
        $this->innerMapper->expects(self::once())
            ->method('map')
            ->with($lineItem)
            ->willReturn($taxable);

        $this->taxationSettingsProvider->expects(self::once())
            ->method('isCalculateAfterPromotionsEnabled')
            ->willReturn(true);

        $discountLineItem = new DiscountLineItem();
        $discountLineItem
            ->setSourceLineItem($lineItem)
            ->setSubtotal($lineItemSubtotal)
            ->setSubtotalAfterDiscounts($lineItemSubtotalAfterDiscounts);
        $discountContext = new DiscountContext();
        $discountContext->setLineItems([$discountLineItem]);

        $this->promotionExecutor->expects(self::once())
            ->method('supports')
            ->with($order)
            ->willReturn(true);
        $this->promotionExecutor->expects(self::once())
            ->method('execute')
            ->with($order)
            ->willReturn($discountContext);

        $expectedTaxable = clone $taxable;
        $expectedTaxable->setPrice(
            BigDecimal::of($expectedTaxablePrice)->toScale(TaxationSettingsProvider::CALCULATION_SCALE)
        );

        self::assertEquals($expectedTaxable, $this->orderLineItemAfterDiscountsMapper->map($lineItem));
    }

    public function getMapProvider(): array
    {
        return [
            'no order discounts' => [
                'lineItemSubtotal' => 2,
                'lineItemSubtotalAfterDiscounts' => 2,
                'expectedTaxablePrice' => 2,
            ],
            'with order discount' => [
                'lineItemSubtotal' => 3,
                'lineItemSubtotalAfterDiscounts' => 2,
                'expectedTaxablePrice' => 2,
            ]
        ];
    }

    public function testMapKitLineItem(): void
    {
        $taxable = new Taxable();
        $taxable->setPrice(1000);
        $taxable->setKitTaxable(true);
        $taxable->setQuantity(2);
        $taxable->addItem($this->createKitItemTaxable(100, 2));
        $taxable->addItem($this->createKitItemTaxable(200, 2));

        $order = new Order();
        $price = new Price();
        $lineItem = new OrderLineItem();
        $lineItem
            ->setPrice($price)
            ->setOrder($order);
        $this->innerMapper->expects(self::once())
            ->method('map')
            ->with($lineItem)
            ->willReturn($taxable);

        $this->taxationSettingsProvider->expects(self::once())
            ->method('isCalculateAfterPromotionsEnabled')
            ->willReturn(true);

        $discountLineItem = new DiscountLineItem();
        $discountLineItem
            ->setSourceLineItem($lineItem)
            ->setPrice(Price::create(1300, 'USD'))
            ->setSubtotal(2600)
            ->setSubtotalAfterDiscounts(2340);
        $discountContext = new DiscountContext();
        $discountContext->setLineItems([$discountLineItem]);

        $this->promotionExecutor->expects(self::once())
            ->method('supports')
            ->with($order)
            ->willReturn(true);
        $this->promotionExecutor->expects(self::once())
            ->method('execute')
            ->with($order)
            ->willReturn($discountContext);

        $actualTaxable = $this->orderLineItemAfterDiscountsMapper->map($lineItem);
        $items = [];
        foreach ($actualTaxable->getItems() as $item) {
            $items[] = $item;
        }

        self::assertEquals(BigDecimal::of(899.99997)->toScale(12), $actualTaxable->getPrice());
        self::assertEquals(BigDecimal::of(90.00001)->toScale(6), $items[0]->getPrice());
        self::assertEquals(BigDecimal::of(180.000020)->toScale(6), $items[1]->getPrice());
    }

    private function createKitItemTaxable(float $price, int $quantity): Taxable
    {
        return (new Taxable())
            ->setPrice($price)
            ->setCurrency('USD')
            ->setQuantity($quantity);
    }

    public function testMapWithSuborders(): void
    {
        $taxable = new Taxable();
        $taxable->setPrice(4);

        $order = new Order();
        $suborder = new Order();
        $order->addSubOrder($suborder);
        $price = new Price();
        $lineItem = new OrderLineItem();
        $lineItem
            ->setPrice($price)
            ->setOrder($suborder);
        $this->innerMapper->expects(self::once())
            ->method('map')
            ->with($lineItem)
            ->willReturn($taxable);

        $this->taxationSettingsProvider->expects(self::once())
            ->method('isCalculateAfterPromotionsEnabled')
            ->willReturn(true);

        $discountLineItem = new DiscountLineItem();
        $discountLineItem
            ->setSourceLineItem($lineItem)
            ->setSubtotal(3)
            ->setSubtotalAfterDiscounts(2);
        $discountContext = new DiscountContext();
        $discountContext->setLineItems([$discountLineItem]);

        $this->promotionExecutor->expects(self::once())
            ->method('supports')
            ->with($suborder)
            ->willReturn(true);
        $this->promotionExecutor->expects(self::once())
            ->method('execute')
            ->with($suborder)
            ->willReturn($discountContext);

        $expectedTaxable = clone $taxable;
        $expectedTaxable->setPrice(
            BigDecimal::of(2)->toScale(TaxationSettingsProvider::CALCULATION_SCALE)
        );

        self::assertEquals($expectedTaxable, $this->orderLineItemAfterDiscountsMapper->map($lineItem));
    }
}
