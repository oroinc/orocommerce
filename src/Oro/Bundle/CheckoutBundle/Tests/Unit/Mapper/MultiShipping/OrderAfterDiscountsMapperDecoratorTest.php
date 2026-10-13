<?php

declare(strict_types=1);

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Mapper\MultiShipping;

use Brick\Math\BigDecimal;
use Oro\Bundle\CheckoutBundle\Mapper\MultiShipping\OrderAfterDiscountsMapperDecorator;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PromotionBundle\Discount\DiscountContext;
use Oro\Bundle\PromotionBundle\Discount\DiscountInformation;
use Oro\Bundle\PromotionBundle\Discount\ShippingDiscount;
use Oro\Bundle\PromotionBundle\Executor\PromotionExecutor;
use Oro\Bundle\TaxBundle\Mapper\TaxMapperInterface;
use Oro\Bundle\TaxBundle\Model\Taxable;
use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;
use PHPUnit\Framework\MockObject\MockObject;

class OrderAfterDiscountsMapperDecoratorTest extends \PHPUnit\Framework\TestCase
{
    private TaxMapperInterface&MockObject $innerMapper;
    private TaxationSettingsProvider&MockObject $taxationSettingsProvider;
    private PromotionExecutor&MockObject $promotionExecutor;

    private OrderAfterDiscountsMapperDecorator $mapper;

    #[\Override]
    protected function setUp(): void
    {
        $this->innerMapper = $this->createMock(TaxMapperInterface::class);
        $this->taxationSettingsProvider = $this->createMock(TaxationSettingsProvider::class);
        $this->promotionExecutor = $this->createMock(PromotionExecutor::class);

        $this->mapper = new OrderAfterDiscountsMapperDecorator(
            $this->innerMapper,
            $this->taxationSettingsProvider,
            $this->promotionExecutor
        );
    }

    private function createDiscountContext(float $shippingDiscount): DiscountContext
    {
        $context = new DiscountContext();

        if ($shippingDiscount > 0.0) {
            $context->addShippingDiscountInformation(
                new DiscountInformation(
                    new ShippingDiscount(),
                    $shippingDiscount
                )
            );
        }

        return $context;
    }

    public function testMapOrderWithoutSubOrders(): void
    {
        $order = new Order();
        $taxable = (new Taxable())->setShippingCost(BigDecimal::of('100'));

        $this->innerMapper->expects(self::once())
            ->method('map')
            ->with($order)
            ->willReturn($taxable);

        $this->taxationSettingsProvider->expects(self::never())
            ->method('isCalculateAfterPromotionsEnabled');

        $this->promotionExecutor->expects(self::never())
            ->method('supports');

        self::assertSame($taxable, $this->mapper->map($order));
        self::assertEquals(BigDecimal::of('100'), $taxable->getShippingCost());
    }

    public function testMapCalculateAfterPromotionsDisabled(): void
    {
        $order = new Order();
        $order->addSubOrder(new Order());

        $taxable = (new Taxable())->setShippingCost(BigDecimal::of('100'));

        $this->innerMapper->expects(self::once())
            ->method('map')
            ->with($order)
            ->willReturn($taxable);

        $this->taxationSettingsProvider->expects(self::once())
            ->method('isCalculateAfterPromotionsEnabled')
            ->willReturn(false);

        $this->promotionExecutor->expects(self::never())
            ->method('supports');

        self::assertSame($taxable, $this->mapper->map($order));
        self::assertEquals(BigDecimal::of('100'), $taxable->getShippingCost());
    }

    public function testMapWithoutShippingCost(): void
    {
        $order = new Order();
        $order->addSubOrder(new Order());

        $taxable = new Taxable();

        $this->innerMapper->expects(self::once())
            ->method('map')
            ->with($order)
            ->willReturn($taxable);

        $this->taxationSettingsProvider->expects(self::once())
            ->method('isCalculateAfterPromotionsEnabled')
            ->willReturn(true);

        $this->promotionExecutor->expects(self::never())
            ->method('supports');

        self::assertSame($taxable, $this->mapper->map($order));
        self::assertNull($taxable->getShippingCost());
    }

    public function testMapUnsupportedSubOrder(): void
    {
        $subOrder = new Order();

        $order = new Order();
        $order->addSubOrder($subOrder);

        $taxable = (new Taxable())->setShippingCost(BigDecimal::of('100'));

        $this->innerMapper->expects(self::once())
            ->method('map')
            ->with($order)
            ->willReturn($taxable);

        $this->taxationSettingsProvider->expects(self::once())
            ->method('isCalculateAfterPromotionsEnabled')
            ->willReturn(true);

        $this->promotionExecutor->expects(self::once())
            ->method('supports')
            ->with($subOrder)
            ->willReturn(false);

        $this->promotionExecutor->expects(self::never())
            ->method('execute');

        self::assertSame($taxable, $this->mapper->map($order));
        self::assertEquals(BigDecimal::of('100'), $taxable->getShippingCost());
    }

    public function testMapSubtractsShippingDiscountFromSubOrder(): void
    {
        $subOrder = new Order();

        $order = new Order();
        $order->addSubOrder($subOrder);

        $taxable = (new Taxable())->setShippingCost(BigDecimal::of('100'));

        $this->innerMapper->expects(self::once())
            ->method('map')
            ->with($order)
            ->willReturn($taxable);

        $this->taxationSettingsProvider->expects(self::once())
            ->method('isCalculateAfterPromotionsEnabled')
            ->willReturn(true);

        $this->promotionExecutor->expects(self::once())
            ->method('supports')
            ->with($subOrder)
            ->willReturn(true);

        $this->promotionExecutor->expects(self::once())
            ->method('execute')
            ->with($subOrder)
            ->willReturn($this->createDiscountContext(42.0));

        self::assertSame($taxable, $this->mapper->map($order));
        self::assertEquals(BigDecimal::of('58'), $taxable->getShippingCost());
    }

    public function testMapSubtractsShippingDiscountsFromMultipleSubOrders(): void
    {
        $subOrder1 = new Order();
        $subOrder2 = new Order();

        $order = new Order();
        $order->addSubOrder($subOrder1);
        $order->addSubOrder($subOrder2);

        $taxable = (new Taxable())->setShippingCost(BigDecimal::of('200'));

        $this->innerMapper->expects(self::once())
            ->method('map')
            ->with($order)
            ->willReturn($taxable);

        $this->taxationSettingsProvider->expects(self::once())
            ->method('isCalculateAfterPromotionsEnabled')
            ->willReturn(true);

        $this->promotionExecutor->expects(self::exactly(2))
            ->method('supports')
            ->willReturn(true);

        $this->promotionExecutor->expects(self::exactly(2))
            ->method('execute')
            ->willReturnOnConsecutiveCalls(
                $this->createDiscountContext(42.0),
                $this->createDiscountContext(20.0)
            );

        self::assertSame($taxable, $this->mapper->map($order));
        self::assertEquals(BigDecimal::of('138'), $taxable->getShippingCost());
    }

    public function testMapDoesNotAllowNegativeShippingCost(): void
    {
        $subOrder = new Order();

        $order = new Order();
        $order->addSubOrder($subOrder);

        $taxable = (new Taxable())->setShippingCost(BigDecimal::of('40'));

        $this->innerMapper->expects(self::once())
            ->method('map')
            ->with($order)
            ->willReturn($taxable);

        $this->taxationSettingsProvider->expects(self::once())
            ->method('isCalculateAfterPromotionsEnabled')
            ->willReturn(true);

        $this->promotionExecutor->expects(self::once())
            ->method('supports')
            ->with($subOrder)
            ->willReturn(true);

        $this->promotionExecutor->expects(self::once())
            ->method('execute')
            ->with($subOrder)
            ->willReturn($this->createDiscountContext(50.0));

        self::assertSame($taxable, $this->mapper->map($order));
        self::assertEquals(BigDecimal::zero(), $taxable->getShippingCost());
    }
}
