<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\OrderTax\Mapper;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PromotionBundle\Discount\DiscountContext;
use Oro\Bundle\PromotionBundle\Executor\PromotionExecutor;
use Oro\Bundle\PromotionBundle\OrderTax\Mapper\OrderAfterDiscountsMapper;
use Oro\Bundle\TaxBundle\Mapper\TaxMapperInterface;
use Oro\Bundle\TaxBundle\Model\Taxable;
use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;

class OrderAfterDiscountsMapperTest extends \PHPUnit\Framework\TestCase
{
    /** @var TaxMapperInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $innerMapper;

    /** @var TaxationSettingsProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $taxationSettingsProvider;

    /** @var PromotionExecutor|\PHPUnit\Framework\MockObject\MockObject */
    private $promotionExecutor;

    private OrderAfterDiscountsMapper $orderAfterDiscountsMapper;

    protected function setUp(): void
    {
        $this->innerMapper = $this->createMock(TaxMapperInterface::class);
        $this->taxationSettingsProvider = $this->createMock(TaxationSettingsProvider::class);
        $this->promotionExecutor = $this->createMock(PromotionExecutor::class);

        $this->orderAfterDiscountsMapper = new OrderAfterDiscountsMapper(
            $this->innerMapper,
            $this->taxationSettingsProvider,
            $this->promotionExecutor
        );
    }

    public function testMapCalculateAfterPromotionsDisabled(): void
    {
        $taxable = new Taxable();
        $taxable->setShippingCost(2);

        $order = new Order();
        $this->innerMapper->expects(self::once())
            ->method('map')
            ->with($order)
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

        self::assertEquals(clone $taxable, $this->orderAfterDiscountsMapper->map($order));
    }

    public function testMapExecutorNotSupportedEntity(): void
    {
        $taxable = new Taxable();
        $taxable->setShippingCost(2);

        $order = new Order();
        $this->innerMapper->expects(self::once())
            ->method('map')
            ->with($order)
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

        self::assertEquals(clone $taxable, $this->orderAfterDiscountsMapper->map($order));
    }

    public function testMap(): void
    {
        $taxable = new Taxable();
        $taxable->setShippingCost(2);

        $order = new Order();
        $this->innerMapper->expects(self::once())
            ->method('map')
            ->with($order)
            ->willReturn($taxable);

        $this->taxationSettingsProvider->expects(self::once())
            ->method('isCalculateAfterPromotionsEnabled')
            ->willReturn(true);

        $discountContext = new DiscountContext();
        $discountContext
            ->setSubtotal(2)
            ->setShippingCost(1);
        $this->promotionExecutor->expects(self::once())
            ->method('supports')
            ->with($order)
            ->willReturn(true);
        $this->promotionExecutor->expects(self::once())
            ->method('execute')
            ->with($order)
            ->willReturn($discountContext);

        $expectedTaxable = clone $taxable;
        $expectedTaxable->setShippingCost($discountContext->getShippingCost());

        self::assertEquals($expectedTaxable, $this->orderAfterDiscountsMapper->map($order));
    }
}
