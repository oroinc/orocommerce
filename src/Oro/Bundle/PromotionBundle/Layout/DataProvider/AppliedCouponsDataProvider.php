<?php

namespace Oro\Bundle\PromotionBundle\Layout\DataProvider;

use Doctrine\Common\Collections\Collection;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PromotionBundle\Entity\AppliedCoupon;
use Oro\Bundle\PromotionBundle\Entity\AppliedCouponsAwareInterface;
use Oro\Bundle\PromotionBundle\Entity\Promotion;

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
     * @param AppliedCouponsAwareInterface $entity
     * @return Collection|AppliedCoupon[]
     */
    public function getAppliedCoupons(AppliedCouponsAwareInterface $entity)
    {
        return $entity->getAppliedCoupons();
    }

    /**
     * @param AppliedCouponsAwareInterface $entity
     * @return array
     */
    public function getPromotionsForAppliedCoupons(AppliedCouponsAwareInterface $entity)
    {
        $promotionIds = $entity->getAppliedCoupons()->map(function (AppliedCoupon $appliedCoupon) {
            return $appliedCoupon->getSourcePromotionId();
        })->toArray();

        return $this->registry->getManagerForClass(Promotion::class)
            ->getRepository(Promotion::class)
            ->getPromotionsWithLabelsByIds($promotionIds);
    }

    public function hasAppliedCoupons(AppliedCouponsAwareInterface $entity): bool
    {
        return !$entity->getAppliedCoupons()->isEmpty();
    }
}
