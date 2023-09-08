<?php

namespace Oro\Bundle\PromotionBundle\Layout\DataProvider;

use Doctrine\Common\Collections\Collection;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PromotionBundle\Entity\AppliedCoupon;
use Oro\Bundle\PromotionBundle\Entity\AppliedCouponsAwareInterface;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Provider\PromotionProvider;
use Oro\Bundle\PromotionBundle\RuleFiltration\ShippingFiltrationService;

/**
 * Applied coupons data provider.
 */
class AppliedCouponsDataProvider
{
    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @var PromotionProvider
     */
    private $promotionProvider;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function setPromotionProvider(PromotionProvider $promotionProvider): void
    {
        $this->promotionProvider = $promotionProvider;
    }

    /**
     * @param AppliedCouponsAwareInterface $entity
     * @return Collection|AppliedCoupon[]
     */
    public function getAppliedCoupons(AppliedCouponsAwareInterface $entity)
    {
        return $entity->getAppliedCoupons()->filter(function (AppliedCoupon $appliedCoupon) use ($entity) {
            $promotionId = $appliedCoupon->getSourcePromotionId();
            $promotion = $this->registry->getManager()->find(Promotion::class, $promotionId);
            return $this->promotionProvider->isPromotionApplicable(
                $entity,
                $promotion,
                [ShippingFiltrationService::class => true]
            );
        });
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
        return !$this->getAppliedCoupons($entity)->isEmpty();
    }
}
