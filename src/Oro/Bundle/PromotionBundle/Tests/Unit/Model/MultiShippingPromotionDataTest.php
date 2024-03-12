<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Model;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Model\ShippingAwareInterface;
use Oro\Bundle\PromotionBundle\Discount\DiscountLineItem;
use Oro\Bundle\PromotionBundle\Entity\DiscountConfiguration;
use Oro\Bundle\PromotionBundle\Entity\PromotionDataInterface;
use Oro\Bundle\PromotionBundle\Model\MultiShippingPromotionData;
use Oro\Bundle\RuleBundle\Entity\RuleInterface;
use Oro\Bundle\SegmentBundle\Entity\Segment;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class MultiShippingPromotionDataTest extends \PHPUnit\Framework\TestCase
{
    /** @var PromotionDataInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $promotionData;

    /** @var DiscountLineItem[]|\PHPUnit\Framework\MockObject\MockObject[] */
    private $lineItems;

    /** @var MultiShippingPromotionData */
    private $multiShippingPromotionData;

    protected function setUp(): void
    {
        $this->promotionData = $this->createMock(PromotionDataInterface::class);
        $this->lineItems = [
            $this->createMock(DiscountLineItem::class),
            $this->createMock(DiscountLineItem::class)
        ];

        $this->multiShippingPromotionData = new MultiShippingPromotionData(
            $this->promotionData,
            $this->lineItems
        );
    }

    public function testGetId(): void
    {
        $id = 123;

        $this->promotionData->expects(self::once())
            ->method('getId')
            ->willReturn($id);

        self::assertSame($id, $this->multiShippingPromotionData->getId());
    }

    public function testGetDiscountConfiguration(): void
    {
        $discountConfiguration = $this->createMock(DiscountConfiguration::class);

        $this->promotionData->expects(self::once())
            ->method('getDiscountConfiguration')
            ->willReturn($discountConfiguration);

        self::assertSame($discountConfiguration, $this->multiShippingPromotionData->getDiscountConfiguration());
    }

    public function testIsUseCouponsWhenFalse(): void
    {
        $this->promotionData->expects(self::once())
            ->method('isUseCoupons')
            ->willReturn(false);

        self::assertFalse($this->multiShippingPromotionData->isUseCoupons());
    }

    public function testIsUseCouponsWhenTrue(): void
    {
        $this->promotionData->expects(self::once())
            ->method('isUseCoupons')
            ->willReturn(true);

        self::assertTrue($this->multiShippingPromotionData->isUseCoupons());
    }

    public function testGetCoupons(): void
    {
        $coupons = $this->createMock(Collection::class);

        $this->promotionData->expects(self::once())
            ->method('getCoupons')
            ->willReturn($coupons);

        self::assertSame($coupons, $this->multiShippingPromotionData->getCoupons());
    }

    public function testGetProductsSegment(): void
    {
        $productsSegment = $this->createMock(Segment::class);

        $this->promotionData->expects(self::once())
            ->method('getProductsSegment')
            ->willReturn($productsSegment);

        self::assertSame($productsSegment, $this->multiShippingPromotionData->getProductsSegment());
    }

    public function testGetRule(): void
    {
        $rule = $this->createMock(RuleInterface::class);

        $this->promotionData->expects(self::once())
            ->method('getRule')
            ->willReturn($rule);

        self::assertSame($rule, $this->multiShippingPromotionData->getRule());
    }

    public function testGetSchedules(): void
    {
        $schedules = $this->createMock(Collection::class);

        $this->promotionData->expects(self::once())
            ->method('getSchedules')
            ->willReturn($schedules);

        self::assertSame($schedules, $this->multiShippingPromotionData->getSchedules());
    }

    public function testGetScopes(): void
    {
        $scopes = $this->createMock(Collection::class);

        $this->promotionData->expects(self::once())
            ->method('getScopes')
            ->willReturn($scopes);

        self::assertSame($scopes, $this->multiShippingPromotionData->getScopes());
    }

    public function testGetShippingCostForUnsupportedLineItems(): void
    {
        $this->lineItems[0]->expects(self::once())
            ->method('getSourceLineItem')
            ->willReturn($this->createMock(\stdClass::class));
        $this->lineItems[1]->expects(self::once())
            ->method('getSourceLineItem')
            ->willReturn($this->createMock(\stdClass::class));

        self::assertNull($this->multiShippingPromotionData->getShippingCost());
    }

    public function testGetShippingCostWhenNoShippingCostForLineItems(): void
    {
        $sourceLineItem1 = $this->createMock(ShippingAwareInterface::class);
        $sourceLineItem1->expects(self::once())
            ->method('getShippingCost')
            ->willReturn(null);
        $sourceLineItem2 = $this->createMock(ShippingAwareInterface::class);
        $sourceLineItem2->expects(self::once())
            ->method('getShippingCost')
            ->willReturn(null);

        $this->lineItems[0]->expects(self::once())
            ->method('getSourceLineItem')
            ->willReturn($sourceLineItem1);
        $this->lineItems[1]->expects(self::once())
            ->method('getSourceLineItem')
            ->willReturn($sourceLineItem2);

        self::assertNull($this->multiShippingPromotionData->getShippingCost());
    }

    public function testGetShippingCost(): void
    {
        $sourceLineItem1 = $this->createMock(ShippingAwareInterface::class);
        $sourceLineItem1->expects(self::once())
            ->method('getShippingCost')
            ->willReturn(Price::create(11.0, 'EUR'));
        $sourceLineItem2 = $this->createMock(ShippingAwareInterface::class);
        $sourceLineItem2->expects(self::once())
            ->method('getShippingCost')
            ->willReturn(Price::create(12.0, 'EUR'));

        $this->lineItems[0]->expects(self::once())
            ->method('getSourceLineItem')
            ->willReturn($sourceLineItem1);
        $this->lineItems[1]->expects(self::once())
            ->method('getSourceLineItem')
            ->willReturn($sourceLineItem2);

        self::assertEquals(Price::create(23.0, 'EUR'), $this->multiShippingPromotionData->getShippingCost());
    }

    public function testGetLineItems(): void
    {
        self::assertSame($this->lineItems, $this->multiShippingPromotionData->getLineItems());
    }
}
