<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\RuleFiltration;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PromotionBundle\Context\ContextDataConverterInterface;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Entity\Repository\CouponRepository;
use Oro\Bundle\PromotionBundle\Model\AppliedPromotionData;
use Oro\Bundle\PromotionBundle\RuleFiltration\CouponFiltrationService;
use Oro\Bundle\RuleBundle\Entity\RuleOwnerInterface;
use Oro\Bundle\RuleBundle\RuleFiltration\RuleFiltrationServiceInterface;
use Oro\Component\Testing\Unit\EntityTrait;

class CouponFiltrationServiceTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var RuleFiltrationServiceInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $filtrationService;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var CouponFiltrationService */
    private $couponFiltrationService;

    protected function setUp(): void
    {
        $this->filtrationService = $this->createMock(RuleFiltrationServiceInterface::class);
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->couponFiltrationService = new CouponFiltrationService($this->filtrationService, $this->registry);
    }

    public function testGetFilteredRuleOwnersWithNotPromotionDataInterfaceRuleOwners()
    {
        $ruleOwners = [new \stdClass()];
        $context = [];

        $this->filtrationService->expects($this->once())
            ->method('getFilteredRuleOwners')
            ->with([], $context)
            ->willReturnCallback(function ($ruleOwners) {
                return $ruleOwners;
            });

        $this->registry->expects($this->never())
            ->method('getManagerForClass');

        self::assertEmpty($this->couponFiltrationService->getFilteredRuleOwners($ruleOwners, $context));
    }

    public function testGetFilteredRuleOwnersWithNoAppliedCouponsAndRuleOwnerUsesCoupons()
    {
        $appliedPromotion = (new Promotion())->setUseCoupons(true);
        $ruleOwners = [$appliedPromotion];
        $context = [];

        $this->filtrationService->expects($this->once())
            ->method('getFilteredRuleOwners')
            ->with([], $context)
            ->willReturnCallback(function ($ruleOwners) {
                return $ruleOwners;
            });

        $this->registry->expects($this->never())
            ->method('getManagerForClass');

        self::assertEmpty($this->couponFiltrationService->getFilteredRuleOwners($ruleOwners, $context));
    }

    public function testGetFilteredRuleOwnersWithNoAppliedCouponsAndRuleOwnerNotUsesCoupons()
    {
        $appliedPromotion = (new Promotion())->setUseCoupons(false);
        $ruleOwners = [$appliedPromotion];
        $context = [];

        $this->filtrationService->expects($this->once())
            ->method('getFilteredRuleOwners')
            ->with([$appliedPromotion], $context)
            ->willReturnCallback(function ($ruleOwners) {
                return $ruleOwners;
            });

        $this->registry->expects($this->never())
            ->method('getManagerForClass');

        self::assertEquals(
            [$appliedPromotion],
            $this->couponFiltrationService->getFilteredRuleOwners($ruleOwners, $context)
        );
    }

    public function testGetFilteredPromotionsWithAppliedCoupons()
    {
        $appliedPromotionWithCoupon = $this->getEntity(Promotion::class, ['useCoupons' => true, 'id' => 5]);
        $appliedPromotionWithoutCoupon = $this->getEntity(Promotion::class, ['useCoupons' => true, 'id' => 7]);
        $ruleOwners = [$appliedPromotionWithCoupon, $appliedPromotionWithoutCoupon];

        $appliedCoupons = [(new Coupon())->setCode('XYZ')];
        $context = [ContextDataConverterInterface::APPLIED_COUPONS => new ArrayCollection($appliedCoupons)];

        $this->filtrationService->expects($this->once())
            ->method('getFilteredRuleOwners')
            ->with([$appliedPromotionWithCoupon], $context)
            ->willReturnCallback(function ($ruleOwners) {
                return $ruleOwners;
            });

        $repository = $this->createMock(CouponRepository::class);
        $repository->expects($this->once())
            ->method('getPromotionsWithMatchedCoupons')
            ->with([$appliedPromotionWithCoupon, $appliedPromotionWithoutCoupon], ['XYZ'])
            ->willReturn([5]);

        $entityManager = $this->createMock(EntityManager::class);

        $entityManager->expects($this->once())
            ->method('getRepository')
            ->with(Coupon::class)
            ->willReturn($repository);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(Coupon::class)
            ->willReturn($entityManager);

        self::assertEquals(
            [$appliedPromotionWithCoupon],
            $this->couponFiltrationService->getFilteredRuleOwners($ruleOwners, $context)
        );
    }

    public function testGetFilteredPromotionsDataWithAppliedCoupons()
    {
        $appliedCoupon = (new Coupon())->setCode('XYZ');
        $removedCoupon = (new Coupon())->setCode('Removed');

        $promotionWithCoupon = (new AppliedPromotionData())->setUseCoupons(true)->addCoupon($appliedCoupon);
        $promotionWithRemovedCoupon = (new AppliedPromotionData())->setUseCoupons(true)->addCoupon($removedCoupon);
        $ruleOwners = [$promotionWithCoupon, $promotionWithRemovedCoupon];

        $context = [ContextDataConverterInterface::APPLIED_COUPONS => new ArrayCollection([$appliedCoupon])];

        $this->filtrationService->expects($this->once())
            ->method('getFilteredRuleOwners')
            ->with([$promotionWithCoupon], $context)
            ->willReturnCallback(function ($ruleOwners) {
                return $ruleOwners;
            });

        $this->registry->expects($this->never())
            ->method('getManagerForClass');

        self::assertEquals(
            [$promotionWithCoupon],
            $this->couponFiltrationService->getFilteredRuleOwners($ruleOwners, $context)
        );
    }

    public function testGetFilteredPromotionsDataAndCheckThatCouponIsUsedOnce()
    {
        $appliedCoupon = (new Coupon())->setCode('XYZ');
        $appliedPromotion = (new AppliedPromotionData())->setUseCoupons(true)->addCoupon($appliedCoupon);

        $context = [ContextDataConverterInterface::APPLIED_COUPONS => new ArrayCollection([$appliedCoupon])];

        $this->filtrationService->expects($this->once())
            ->method('getFilteredRuleOwners')
            ->with([$appliedPromotion], $context)
            ->willReturnCallback(function ($ruleOwners) {
                return $ruleOwners;
            });

        $this->registry->expects($this->never())
            ->method('getManagerForClass');

        self::assertEquals(
            [$appliedPromotion],
            $this->couponFiltrationService->getFilteredRuleOwners([$appliedPromotion], $context)
        );
    }

    public function testFilterIsSkippable()
    {
        $this->filtrationService->expects($this->never())
            ->method('getFilteredRuleOwners');

        $ruleOwner = $this->createMock(RuleOwnerInterface::class);
        $this->couponFiltrationService->getFilteredRuleOwners(
            [$ruleOwner],
            ['skip_filters' => [get_class($this->couponFiltrationService) => true]]
        );
    }
}
