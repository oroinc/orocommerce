<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\PromotionBundle\Entity\AppliedCoupon;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Layout\DataProvider\AppliedCouponsDataProvider;
use Oro\Bundle\PromotionBundle\Manager\FrontendAppliedCouponManager;
use Oro\Bundle\PromotionBundle\Model\FrontendAppliedCoupon;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;

class AppliedCouponsDataProviderTest extends \PHPUnit\Framework\TestCase
{
    private FrontendAppliedCouponManager|MockObject $frontendAppliedCouponManager;
    private AppliedCouponsDataProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->frontendAppliedCouponManager = $this->createMock(FrontendAppliedCouponManager::class);

        $this->provider = new AppliedCouponsDataProvider($this->frontendAppliedCouponManager);
    }

    private function getFrontendAppliedCoupon(
        AppliedCoupon $appliedCoupon,
        Promotion $promotion
    ): FrontendAppliedCoupon {
        return new FrontendAppliedCoupon($appliedCoupon, $promotion);
    }

    private function getPromotion(int $id): Promotion
    {
        $promotion = new Promotion();
        ReflectionUtil::setId($promotion, $id);

        return $promotion;
    }

    public function testGetAppliedCoupons(): void
    {
        $entity = $this->createMock(\stdClass::class);
        $appliedCoupon1 = new AppliedCoupon();
        $appliedCoupon2 = new AppliedCoupon();

        $this->frontendAppliedCouponManager->expects(self::once())
            ->method('getAppliedCoupons')
            ->with(self::identicalTo($entity))
            ->willReturn([
                $this->getFrontendAppliedCoupon($appliedCoupon1, new Promotion()),
                $this->getFrontendAppliedCoupon($appliedCoupon2, new Promotion())
            ]);

        self::assertSame(
            [$appliedCoupon1, $appliedCoupon2],
            $this->provider->getAppliedCoupons($entity)
        );
        // test memory cache
        self::assertSame(
            [$appliedCoupon1, $appliedCoupon2],
            $this->provider->getAppliedCoupons($entity)
        );
    }

    public function testGetPromotionsForAppliedCoupons(): void
    {
        $entity = $this->createMock(\stdClass::class);
        $promotion1 = $this->getPromotion(1);
        $promotion2 = $this->getPromotion(2);

        $this->frontendAppliedCouponManager->expects(self::once())
            ->method('getAppliedCoupons')
            ->with(self::identicalTo($entity))
            ->willReturn([
                $this->getFrontendAppliedCoupon(new AppliedCoupon(), $promotion1),
                $this->getFrontendAppliedCoupon(new AppliedCoupon(), $promotion2),
                $this->getFrontendAppliedCoupon(new AppliedCoupon(), $promotion1)
            ]);

        self::assertSame(
            [$promotion1->getId() => $promotion1, $promotion2->getId() => $promotion2],
            $this->provider->getPromotionsForAppliedCoupons($entity)
        );
        // test memory cache
        self::assertSame(
            [$promotion1->getId() => $promotion1, $promotion2->getId() => $promotion2],
            $this->provider->getPromotionsForAppliedCoupons($entity)
        );
    }

    public function testHasAppliedCouponsWhenEntityHasAppliedCoupons(): void
    {
        $entity = $this->createMock(\stdClass::class);

        $this->frontendAppliedCouponManager->expects(self::once())
            ->method('getAppliedCoupons')
            ->with(self::identicalTo($entity))
            ->willReturn([$this->getFrontendAppliedCoupon(new AppliedCoupon(), new Promotion())]);

        self::assertTrue($this->provider->hasAppliedCoupons($entity));
        // test memory cache
        self::assertTrue($this->provider->hasAppliedCoupons($entity));
    }

    public function testHasAppliedCouponsWhenEntityDoesNotHaveAppliedCoupons(): void
    {
        $entity = $this->createMock(\stdClass::class);

        $this->frontendAppliedCouponManager->expects(self::once())
            ->method('getAppliedCoupons')
            ->with(self::identicalTo($entity))
            ->willReturn([]);

        self::assertFalse($this->provider->hasAppliedCoupons($entity));
        // test memory cache
        self::assertFalse($this->provider->hasAppliedCoupons($entity));
    }
}
