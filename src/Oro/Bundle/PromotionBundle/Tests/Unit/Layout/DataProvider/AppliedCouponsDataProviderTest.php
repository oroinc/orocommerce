<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Layout\DataProvider;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PromotionBundle\Discount\DiscountContextInterface;
use Oro\Bundle\PromotionBundle\Discount\DiscountInterface;
use Oro\Bundle\PromotionBundle\Entity\AppliedCoupon;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Entity\Repository\PromotionRepository;
use Oro\Bundle\PromotionBundle\Layout\DataProvider\AppliedCouponsDataProvider;
use Oro\Bundle\PromotionBundle\Provider\PromotionProvider;
use Oro\Bundle\PromotionBundle\Tests\Unit\Stub\AppliedCouponsAwareStub;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;

class AppliedCouponsDataProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    private MockObject|ManagerRegistry $registry;
    private MockObject|PromotionProvider $promotionProvider;
    private AppliedCouponsDataProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->promotionProvider = $this->createMock(PromotionProvider::class);
        $this->provider = new AppliedCouponsDataProvider($this->registry, $this->promotionProvider);
    }

    public function testGetAppliedCouponsNoSubtotalDiscounts()
    {
        $entity = $this->createMock(AppliedCouponsAwareStub::class);
        $entity->expects(self::once())
            ->method('getAppliedCoupons')
            ->willReturn(new ArrayCollection());

        self::assertEmpty($this->provider->getAppliedCoupons($entity));
    }

    /**
     * @dataProvider getAppliedCouponsDataProvider
     */
    public function testGetAppliedCoupons(
        array $appliedCoupons,
        array $activePromotionIds,
        array $expectedCoupons
    ) {
        $appliedCouponsAmount = \count($appliedCoupons);
        $coupons = new ArrayCollection($appliedCoupons);
        $entity = $this->createMock(AppliedCouponsAwareStub::class);
        $entity->expects(self::once())
            ->method('getAppliedCoupons')
            ->willReturn($coupons);

        $em = $this->createMock(EntityManager::class);
        $em->expects(self::exactly($appliedCouponsAmount))
            ->method('find')
            ->withAnyParameters()
            ->willReturnCallback(function (string $class, int $id) {
                return $this->getEntity($class, ['id' => $id]);
            });

        $this->promotionProvider->expects(self::exactly($appliedCouponsAmount))
            ->method('isPromotionApplicable')
            ->withAnyParameters()
            ->willReturnCallback(function (object $entity, Promotion $promotion) use ($activePromotionIds) {
                return \in_array($promotion->getId(), $activePromotionIds, true);
            });

        $this->registry->expects(self::exactly($appliedCouponsAmount))
            ->method('getManager')
            ->with(null)
            ->willReturn($em);

        $result = $this->provider->getAppliedCoupons($entity);

        self::assertEquals($expectedCoupons, $result->toArray());
    }

    public function getAppliedCouponsDataProvider(): array
    {
        $coupon1 = $this->createAppliedCoupon(1);
        $coupon2 = $this->createAppliedCoupon(2);

        return [
            [[$coupon1], [], []],
            [[$coupon1], [2], []],
            [[$coupon1], [1], [$coupon1]],
            [[$coupon1, $coupon2], [1], [$coupon1]],
            [[$coupon1, $coupon2], [1, 2], [$coupon1, $coupon2]],
        ];
    }

    private function createAppliedCoupon(int $id): MockObject|AppliedCoupon
    {
        $coupon = $this->createMock(AppliedCoupon::class);
        $coupon->method('getId')->willReturn($id);
        $coupon->method('getSourcePromotionId')->willReturn($id);

        return $coupon;
    }

    private function createDiscount(int $promotionId): MockObject|DiscountInterface
    {
        $promotion = $this->getEntity(Promotion::class, ['id' => $promotionId]);

        $discount = $this->createMock(DiscountInterface::class);
        $discount->method('getPromotion')->willReturn($promotion);

        return $discount;
    }

    /**
     * @dataProvider hasAppliedCouponsDataProvider
     */
    public function testHasAppliedCoupons(
        array $appliedCoupons,
        array $activePromotionIds,
        bool $expectedResult
    ) {
        $appliedCouponsAmount = \count($appliedCoupons);
        $coupons = new ArrayCollection($appliedCoupons);
        $entity = $this->createMock(AppliedCouponsAwareStub::class);
        $entity->expects(self::once())
            ->method('getAppliedCoupons')
            ->willReturn($coupons);

        $em = $this->createMock(EntityManager::class);
        $em->expects(self::exactly($appliedCouponsAmount))
            ->method('find')
            ->withAnyParameters()
            ->willReturnCallback(function (string $class, int $id) {
                return $this->getEntity($class, ['id' => $id]);
            });

        $this->promotionProvider->expects(self::exactly($appliedCouponsAmount))
            ->method('isPromotionApplicable')
            ->withAnyParameters()
            ->willReturnCallback(function (object $entity, Promotion $promotion) use ($activePromotionIds) {
                return \in_array($promotion->getId(), $activePromotionIds, true);
            });

        $this->registry->expects(self::exactly($appliedCouponsAmount))
            ->method('getManager')
            ->with(null)
            ->willReturn($em);

        $result = $this->provider->hasAppliedCoupons($entity);
        self::assertEquals($expectedResult, $result);
    }

    public function hasAppliedCouponsDataProvider(): array
    {
        $coupon1 = $this->createAppliedCoupon(1);
        $coupon2 = $this->createAppliedCoupon(2);

        return [
            [[$coupon1], [], false],
            [[$coupon1], [2], false],
            [[$coupon1], [1], true],
            [[$coupon1, $coupon2], [1], true],
            [[$coupon1, $coupon2], [1, 2], true],
        ];
    }

    public function testGetPromotionsForAppliedCoupons()
    {
        $coupons = new ArrayCollection(
            [
                $this->getEntity(AppliedCoupon::class, ['sourcePromotionId' => 3]),
                $this->getEntity(AppliedCoupon::class, ['sourcePromotionId' => 5])
            ]
        );
        $entity = $this->createMock(AppliedCouponsAwareStub::class);
        $entity->expects(self::once())
            ->method('getAppliedCoupons')
            ->willReturn($coupons);

        $promotions = [3 => $this->getEntity(Promotion::class, ['id' => 3])];
        $repo = $this->createMock(PromotionRepository::class);
        $repo->expects(self::once())
            ->method('getPromotionsWithLabelsByIds')
            ->with([3, 5])
            ->willReturn($promotions);
        $em = $this->createMock(EntityManager::class);
        $em->expects(self::once())
            ->method('getRepository')
            ->with(Promotion::class)
            ->willReturn($repo);

        $this->registry->expects(self::once())
            ->method('getManagerForClass')
            ->with(Promotion::class)
            ->willReturn($em);

        self::assertEquals($promotions, $this->provider->getPromotionsForAppliedCoupons($entity));
    }

    private function getDiscountContext(object $entity, bool $addDiscounts = true): MockObject|DiscountContextInterface
    {
        $context = $this->createMock(DiscountContextInterface::class);
        $this->promotionProvider->method('execute')
            ->with($entity)
            ->willReturn($context);

        if ($addDiscounts) {
            $context->expects(self::once())
                ->method('getShippingDiscounts')
                ->willReturn([]);
            $context->expects(self::once())
                ->method('getLineItemDiscounts')
                ->willReturn([]);
        }

        return $context;
    }
}
