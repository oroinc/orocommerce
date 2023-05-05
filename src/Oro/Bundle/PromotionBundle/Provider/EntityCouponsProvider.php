<?php

namespace Oro\Bundle\PromotionBundle\Provider;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Selectable;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PromotionBundle\Entity\AppliedCoupon;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\PromotionBundle\Entity\CouponsAwareInterface;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Model\PromotionAwareEntityHelper;

/**
 * This service help with getting coupons or applied coupons
 */
class EntityCouponsProvider implements EntityCouponsProviderInterface
{
    public function __construct(
        private DoctrineHelper             $doctrineHelper,
        private PromotionAwareEntityHelper $promotionAwareHelper
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function getCoupons($entity)
    {
        if ($entity instanceof CouponsAwareInterface) {
            return $entity->getCoupons();
        } elseif ($this->promotionAwareHelper->isCouponAware($entity)) {
            return $this->getCouponsByAppliedCoupons($entity->getAppliedCoupons());
        }

        throw new \InvalidArgumentException(
            'Given entity must have is_coupon_aware entity config or ' .
            'implement the Oro\Bundle\PromotionBundle\Entity\CouponsAwareInterface interface'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function createAppliedCouponByCoupon(Coupon $coupon)
    {
        $appliedCoupon = new AppliedCoupon();
        $appliedCoupon
            ->setCouponCode($coupon->getCode())
            ->setSourceCouponId($coupon->getId())
            ->setSourcePromotionId($coupon->getPromotion()->getId());

        return $appliedCoupon;
    }

    /**
     * @param Collection|AppliedCoupon[] $appliedCoupons
     * @return Collection|Selectable
     */
    private function getCouponsByAppliedCoupons(Collection $appliedCoupons)
    {
        $coupons = new ArrayCollection();
        foreach ($appliedCoupons as $appliedCoupon) {
            /** @var Promotion $promotion */
            $promotion = $this->createEntity(Promotion::class, $appliedCoupon->getSourcePromotionId());

            /** @var Coupon $coupon */
            $coupon = $this->createEntity(Coupon::class, $appliedCoupon->getSourceCouponId());
            $coupon
                ->setEnabled(true)
                ->setCode($appliedCoupon->getCouponCode())
                ->setPromotion($promotion)
                ->setUsesPerCoupon(null)
                ->setUsesPerPerson(null);

            $coupons->add($coupon);
        }

        return $coupons;
    }

    /**
     * @param string $entityClass
     * @param mixed $id
     * @return object
     */
    private function createEntity($entityClass, $id)
    {
        $entity = $this->doctrineHelper->createEntityInstance($entityClass);
        $identifierField = $this->doctrineHelper->getSingleEntityIdentifierFieldName($entityClass);
        $reflectionProperty = new \ReflectionProperty($entityClass, $identifierField);
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($entity, $id);

        return $entity;
    }
}
