<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\RuleFiltration;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PromotionBundle\Context\ContextDataConverterInterface;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Entity\Repository\CouponRepository;
use Oro\Bundle\PromotionBundle\Model\AppliedPromotionData;
use Oro\Bundle\PromotionBundle\RuleFiltration\CouponFiltrationService;
use Oro\Bundle\RuleBundle\Entity\RuleOwnerInterface;
use Oro\Bundle\RuleBundle\RuleFiltration\RuleFiltrationServiceInterface;
use Oro\Component\Testing\ReflectionUtil;

class CouponFiltrationServiceTest extends \PHPUnit\Framework\TestCase
{
    /** @var RuleFiltrationServiceInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $baseFiltrationService;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var CouponFiltrationService */
    private $filtrationService;

    #[\Override]
    protected function setUp(): void
    {
        $this->baseFiltrationService = $this->createMock(RuleFiltrationServiceInterface::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->filtrationService = new CouponFiltrationService($this->baseFiltrationService, $this->doctrine);
    }

    private function getPromotion(int $id, bool $useCoupons): Promotion
    {
        $promotion = new Promotion();
        ReflectionUtil::setId($promotion, $id);
        $promotion->setUseCoupons($useCoupons);

        return $promotion;
    }

    private function getCoupon(string $code): Coupon
    {
        $coupon = new Coupon();
        $coupon->setCode($code);

        return $coupon;
    }

    private function getAppliedPromotion(Coupon $coupon): AppliedPromotionData
    {
        $appliedPromotion = new AppliedPromotionData();
        $appliedPromotion->setUseCoupons(true);
        $appliedPromotion->addCoupon($coupon);

        return $appliedPromotion;
    }

    public function testShouldBeSkippable(): void
    {
        $ruleOwners = [$this->createMock(RuleOwnerInterface::class)];

        $this->baseFiltrationService->expects(self::never())
            ->method('getFilteredRuleOwners');

        self::assertSame(
            $ruleOwners,
            $this->filtrationService->getFilteredRuleOwners(
                $ruleOwners,
                ['skip_filters' => [CouponFiltrationService::class => true]]
            )
        );
    }

    public function testGetFilteredRuleOwnersWithNotPromotionDataInterfaceRuleOwners(): void
    {
        $ruleOwners = [new \stdClass()];
        $context = ['key' => 'val'];

        $this->baseFiltrationService->expects(self::once())
            ->method('getFilteredRuleOwners')
            ->with([], $context)
            ->willReturn([]);

        $this->doctrine->expects(self::never())
            ->method('getRepository');

        self::assertSame([], $this->filtrationService->getFilteredRuleOwners($ruleOwners, $context));
    }

    public function testGetFilteredRuleOwnersWithNoAppliedCouponsAndRuleOwnerUsesCoupons(): void
    {
        $ruleOwners = [$this->getPromotion(5, true)];
        $context = ['key' => 'val'];

        $this->baseFiltrationService->expects(self::once())
            ->method('getFilteredRuleOwners')
            ->with([], $context)
            ->willReturn([]);

        $this->doctrine->expects(self::never())
            ->method('getRepository');

        self::assertSame([], $this->filtrationService->getFilteredRuleOwners($ruleOwners, $context));
    }

    public function testGetFilteredRuleOwnersWithNoAppliedCouponsAndRuleOwnerNotUsesCoupons(): void
    {
        $ruleOwners = [$this->getPromotion(5, false)];
        $context = ['key' => 'val'];

        $this->baseFiltrationService->expects(self::once())
            ->method('getFilteredRuleOwners')
            ->with($ruleOwners, $context)
            ->willReturn($ruleOwners);

        $this->doctrine->expects(self::never())
            ->method('getRepository');

        self::assertSame(
            $ruleOwners,
            $this->filtrationService->getFilteredRuleOwners($ruleOwners, $context)
        );
    }

    public function testGetFilteredPromotionsWithAppliedCoupons(): void
    {
        $appliedPromotionWithCoupon = $this->getPromotion(5, true);
        $appliedPromotionWithoutCoupon = $this->getPromotion(7, true);
        $ruleOwners = [$appliedPromotionWithCoupon, $appliedPromotionWithoutCoupon];

        $appliedCoupons = [$this->getCoupon('XYZ'), $this->getCoupon('123')];
        $context = [ContextDataConverterInterface::APPLIED_COUPONS => new ArrayCollection($appliedCoupons)];

        $this->baseFiltrationService->expects(self::exactly(2))
            ->method('getFilteredRuleOwners')
            ->with([$appliedPromotionWithCoupon], $context)
            ->willReturn([$appliedPromotionWithCoupon]);

        $repository = $this->createMock(CouponRepository::class);
        $repository->expects(self::once())
            ->method('getPromotionsWithMatchedCoupons')
            ->with(
                [$appliedPromotionWithCoupon->getId(), $appliedPromotionWithoutCoupon->getId()],
                self::identicalTo(['123', 'XYZ'])
            )
            ->willReturn([5]);

        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(Coupon::class)
            ->willReturn($repository);

        self::assertEquals(
            [$appliedPromotionWithCoupon],
            $this->filtrationService->getFilteredRuleOwners($ruleOwners, $context)
        );
        // test memory cache
        self::assertEquals(
            [$appliedPromotionWithCoupon],
            $this->filtrationService->getFilteredRuleOwners($ruleOwners, $context)
        );
    }

    public function testGetFilteredPromotionsDataWithAppliedCoupons(): void
    {
        $appliedCoupon = $this->getCoupon('XYZ');
        $removedCoupon = $this->getCoupon('Removed');

        $promotionWithCoupon = $this->getAppliedPromotion($appliedCoupon);
        $promotionWithRemovedCoupon = $this->getAppliedPromotion($removedCoupon);
        $ruleOwners = [$promotionWithCoupon, $promotionWithRemovedCoupon];

        $context = [ContextDataConverterInterface::APPLIED_COUPONS => new ArrayCollection([$appliedCoupon])];

        $this->baseFiltrationService->expects(self::once())
            ->method('getFilteredRuleOwners')
            ->with([$promotionWithCoupon], $context)
            ->willReturn([$promotionWithCoupon]);

        $this->doctrine->expects(self::never())
            ->method('getRepository');

        self::assertEquals(
            [$promotionWithCoupon],
            $this->filtrationService->getFilteredRuleOwners($ruleOwners, $context)
        );
    }

    public function testGetFilteredPromotionsDataAndCheckThatCouponIsUsedOnce(): void
    {
        $appliedCoupon = $this->getCoupon('XYZ');
        $ruleOwners = [$this->getAppliedPromotion($appliedCoupon)];

        $context = [ContextDataConverterInterface::APPLIED_COUPONS => new ArrayCollection([$appliedCoupon])];

        $this->baseFiltrationService->expects(self::once())
            ->method('getFilteredRuleOwners')
            ->with($ruleOwners, $context)
            ->willReturn($ruleOwners);

        $this->doctrine->expects(self::never())
            ->method('getRepository');

        self::assertSame(
            $ruleOwners,
            $this->filtrationService->getFilteredRuleOwners($ruleOwners, $context)
        );
    }
}
