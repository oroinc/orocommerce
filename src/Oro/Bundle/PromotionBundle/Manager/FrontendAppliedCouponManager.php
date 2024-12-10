<?php

namespace Oro\Bundle\PromotionBundle\Manager;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\PromotionBundle\Entity\AppliedCoupon;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Model\FrontendAppliedCoupon;
use Oro\Bundle\PromotionBundle\Model\PromotionAwareEntityHelper;
use Oro\Bundle\PromotionBundle\Provider\EntityCouponsProviderInterface;
use Oro\Bundle\PromotionBundle\Provider\PromotionProvider;
use Oro\Bundle\PromotionBundle\ValidationService\CouponApplicabilityValidationService;

/**
 * Provides the following functionality:
 * * get coupons applied to an entity
 * * apply a coupon to an entity
 * * remove an applied coupon from an entity
 * This manager must be used only on the storefront.
 */
class FrontendAppliedCouponManager
{
    private $skippedFilters = [];

    public function __construct(
        private readonly CouponApplicabilityValidationService $couponApplicabilityValidationService,
        private readonly EntityCouponsProviderInterface $entityCouponsProvider,
        private readonly PromotionAwareEntityHelper $promotionAwareHelper,
        private readonly PromotionProvider $promotionProvider,
        private readonly ConfigManager $configManager,
        private readonly ManagerRegistry $doctrine
    ) {
    }

    public function disableFilter(string $filterClass): void
    {
        $this->skippedFilters[$filterClass] = true;
    }

    /**
     * @return FrontendAppliedCoupon[]
     */
    public function getAppliedCoupons(object $entity): array
    {
        $this->assertSupportedEntity($entity);

        $appliedCoupons = $entity->getAppliedCoupons();

        $promotionIds = [];
        foreach ($appliedCoupons as $appliedCoupon) {
            $promotionId = $appliedCoupon->getSourcePromotionId();
            if (null !== $promotionId) {
                $promotionIds[] = $promotionId;
            }
        }
        if (!$promotionIds) {
            return [];
        }

        $promotionIds = array_unique($promotionIds);
        $promotions = $this->loadPromotions($promotionIds);

        $result = [];
        foreach ($appliedCoupons as $appliedCoupon) {
            $promotionId = $appliedCoupon->getSourcePromotionId();
            if (null === $promotionId) {
                continue;
            }
            $promotion = $promotions[$promotionId] ?? null;
            if (null === $promotion) {
                continue;
            }
            if (!$this->promotionProvider->isPromotionApplicable($entity, $promotion, $this->skippedFilters)) {
                continue;
            }
            $result[] = new FrontendAppliedCoupon($appliedCoupon, $promotion);
        }

        return $result;
    }

    public function applyCoupon(
        object $entity,
        string $couponCode,
        ?Collection $errors = null,
        bool $flush = true
    ): bool {
        $this->assertSupportedEntity($entity);

        $coupon = $this->findCoupon($couponCode);
        if (null === $coupon) {
            $errors?->add('oro.promotion.coupon.violation.invalid_coupon_code');

            return false;
        }

        $validationErrors = $this->couponApplicabilityValidationService->getViolations(
            $coupon,
            $entity,
            $this->skippedFilters
        );
        if ($validationErrors) {
            if (null !== $errors) {
                foreach ($validationErrors as $error) {
                    $errors->add($error);
                }
            }

            return false;
        }

        $appliedCoupon = $this->entityCouponsProvider->createAppliedCouponByCoupon($coupon);
        $entity->addAppliedCoupon($appliedCoupon);
        $em = $this->doctrine->getManagerForClass(AppliedCoupon::class);
        $em->persist($appliedCoupon);
        if ($flush) {
            $em->flush();
        }

        return true;
    }

    public function removeAppliedCoupon(
        object $entity,
        AppliedCoupon $appliedCoupon,
        ?Collection $errors = null,
        bool $flush = true
    ): bool {
        $this->assertSupportedEntity($entity);

        if (!$entity->getAppliedCoupons()->contains($appliedCoupon)) {
            $errors?->add('oro.promotion.coupon.violation.remove_coupon.not_found');

            return false;
        }

        $em = $this->doctrine->getManagerForClass(AppliedCoupon::class);
        $entity->removeAppliedCoupon($appliedCoupon);
        $em->remove($appliedCoupon);
        if ($flush) {
            $em->flush();
        }

        return true;
    }

    public function removeAppliedCouponByCode(
        object $entity,
        string $couponCode,
        ?Collection $errors = null,
        bool $flush = true
    ): bool {
        $this->assertSupportedEntity($entity);

        $appliedCoupon = $this->findAppliedCoupon($entity->getAppliedCoupons(), $couponCode);
        if (null === $appliedCoupon) {
            $errors?->add('oro.promotion.coupon.violation.remove_coupon.not_found');

            return false;
        }

        $em = $this->doctrine->getManagerForClass(AppliedCoupon::class);
        $entity->removeAppliedCoupon($appliedCoupon);
        $em->remove($appliedCoupon);
        if ($flush) {
            $em->flush();
        }

        return true;
    }

    private function assertSupportedEntity(object $entity): void
    {
        if (!$this->promotionAwareHelper->isCouponAware($entity)) {
            throw new \LogicException('The entity must be coupon aware.');
        }
        if ($this->promotionAwareHelper->isPromotionAware($entity)) {
            throw new \LogicException('The entity must be not promotion aware.');
        }
    }

    private function findCoupon(string $couponCode): ?Coupon
    {
        return $this->doctrine->getRepository(Coupon::class)->getSingleCouponByCode(
            $couponCode,
            $this->isCaseInsensitiveCouponSearch()
        );
    }

    private function findAppliedCoupon(Collection $appliedCoupons, string $couponCode): ?AppliedCoupon
    {
        $foundAppliedCoupon = null;
        $caseInsensitiveSearch = $this->isCaseInsensitiveCouponSearch();
        /** @var AppliedCoupon $appliedCoupon */
        foreach ($appliedCoupons as $appliedCoupon) {
            if ($this->areCouponCodesEqual($appliedCoupon->getCouponCode(), $couponCode, $caseInsensitiveSearch)) {
                $foundAppliedCoupon = $appliedCoupon;
                break;
            }
        }

        return $foundAppliedCoupon;
    }

    private function areCouponCodesEqual(string $couponCode1, string $couponCode2, bool $caseInsensitiveSearch): bool
    {
        return $caseInsensitiveSearch
            ? strtoupper($couponCode1) === strtoupper($couponCode2)
            : $couponCode1 === $couponCode2;
    }

    private function isCaseInsensitiveCouponSearch(): bool
    {
        return (bool)$this->configManager->get('oro_promotion.case_insensitive_coupon_search');
    }

    /**
     * @param int[] $promotionIds
     *
     * @return Promotion[] [promotion id => promotion, ...]
     */
    private function loadPromotions(array $promotionIds): array
    {
        /** @var EntityManagerInterface $em */
        $em = $this->doctrine->getManagerForClass(Promotion::class);
        /** @var Promotion[] $promotions */
        $promotions = $em->createQueryBuilder()
            ->select('p, labels, rule')
            ->from(Promotion::class, 'p')
            ->innerJoin('p.rule', 'rule')
            ->leftJoin('p.labels', 'labels')
            ->where('p.id IN (:ids)')
            ->setParameter('ids', $promotionIds)
            ->getQuery()
            ->getResult();

        $result = [];
        foreach ($promotions as $promotion) {
            $result[$promotion->getId()] = $promotion;
        }

        return $result;
    }
}
