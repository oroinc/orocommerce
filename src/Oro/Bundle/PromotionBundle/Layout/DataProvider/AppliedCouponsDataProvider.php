<?php

namespace Oro\Bundle\PromotionBundle\Layout\DataProvider;

use Oro\Bundle\PromotionBundle\Entity\AppliedCoupon;
use Oro\Bundle\PromotionBundle\Manager\FrontendAppliedCouponManager;
use Oro\Bundle\PromotionBundle\Model\FrontendAppliedCoupon;

/**
 * Applied coupons data provider.
 */
class AppliedCouponsDataProvider
{
    private array $cache = [];

    public function __construct(
        private readonly FrontendAppliedCouponManager $frontendAppliedCouponManager
    ) {
    }

    /**
     * @return AppliedCoupon[]
     */
    public function getAppliedCoupons(object $entity): array
    {
        return array_map(function (FrontendAppliedCoupon $appliedCoupon) {
            return $appliedCoupon->getAppliedCoupon();
        }, $this->getCachedAppliedCoupons($entity));
    }

    /**
     * @return array [promotion id => promotion, ...]
     */
    public function getPromotionsForAppliedCoupons(object $entity): array
    {
        $promotions = [];
        $appliedCoupons = $this->getCachedAppliedCoupons($entity);
        foreach ($appliedCoupons as $appliedCoupon) {
            $promotion = $appliedCoupon->getPromotion();
            $promotions[$promotion->getId()] = $promotion;
        }

        return $promotions;
    }

    public function hasAppliedCoupons(object $entity): bool
    {
        return (bool)$this->getCachedAppliedCoupons($entity);
    }

    /**
     * @return FrontendAppliedCoupon[]
     */
    private function getCachedAppliedCoupons(object $entity): array
    {
        $cacheKey = spl_object_hash($entity);
        if (!isset($this->cache[$cacheKey])) {
            $this->cache[$cacheKey] = $this->frontendAppliedCouponManager->getAppliedCoupons($entity);
        }

        return $this->cache[$cacheKey];
    }
}
