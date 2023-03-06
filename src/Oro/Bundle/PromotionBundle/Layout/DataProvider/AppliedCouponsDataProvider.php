<?php

namespace Oro\Bundle\PromotionBundle\Layout\DataProvider;

use Doctrine\Common\Collections\Collection;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PromotionBundle\Entity\AppliedCoupon;
use Oro\Bundle\PromotionBundle\Entity\Promotion;

/**
 * Applied coupons data provider.
 */
class AppliedCouponsDataProvider
{
    /**
     * @var ManagerRegistry
     */
    private $registry;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param object $entity
     * @return Collection|AppliedCoupon[]
     */
    public function getAppliedCoupons(object $entity)
    {
        return $entity->getAppliedCoupons();
    }

    /**
     * @param object $entity
     * @return array
     */
    public function getPromotionsForAppliedCoupons(object $entity)
    {
        $promotionIds = $entity->getAppliedCoupons()->map(function (AppliedCoupon $appliedCoupon) {
            return $appliedCoupon->getSourcePromotionId();
        })->toArray();

        return $this->registry->getManagerForClass(Promotion::class)
            ->getRepository(Promotion::class)
            ->getPromotionsWithLabelsByIds($promotionIds);
    }

    public function hasAppliedCoupons(object $entity): bool
    {
        return !$entity->getAppliedCoupons()->isEmpty();
    }
}
