<?php

namespace Oro\Bundle\PromotionBundle\Provider;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Selectable;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PromotionBundle\Entity\AppliedCoupon;
use Oro\Bundle\PromotionBundle\Entity\AppliedCouponsAwareInterface;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\PromotionBundle\Entity\CouponsAwareInterface;
use Oro\Bundle\PromotionBundle\Entity\Promotion;

/**
 * This service allows to get applied coupons from entities to which coupons were applied
 */
class EntityCouponsProvider implements EntityCouponsProviderInterface
{
    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getCoupons($entity)
    {
        if ($entity instanceof CouponsAwareInterface) {
            return $entity->getCoupons();
        } elseif ($entity instanceof AppliedCouponsAwareInterface) {
            return $this->getCouponsByAppliedCoupons($entity->getAppliedCoupons());
        }

        throw new \InvalidArgumentException(sprintf(
            'Given entity must implement either %s or %s',
            CouponsAwareInterface::class,
            AppliedCouponsAwareInterface::class
        ));
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
            $coupon->setCode($appliedCoupon->getCouponCode());
            $coupon->setPromotion($promotion);

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
