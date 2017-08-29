<?php

namespace Oro\Bundle\PromotionBundle\Mapper;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\PromotionBundle\Entity\AppliedCoupon;
use Oro\Bundle\PromotionBundle\Entity\AppliedPromotion as AppliedPromotionEntity;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\PromotionBundle\Entity\DiscountConfiguration;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Entity\PromotionDataInterface;
use Oro\Bundle\PromotionBundle\Model\AppliedPromotionData;
use Oro\Bundle\PromotionBundle\Normalizer\NormalizerInterface;

/**
 * Maps promotion data to/from AppliedPromotion entity.
 */
class AppliedPromotionMapper
{
    /**
     * @var NormalizerInterface
     */
    private $promotionNormalizer;

    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @param ManagerRegistry $registry
     * @param NormalizerInterface $normalizer
     */
    public function __construct(ManagerRegistry $registry, NormalizerInterface $normalizer)
    {
        $this->registry = $registry;
        $this->promotionNormalizer = $normalizer;
    }

    /**
     * @param PromotionDataInterface $promotion
     * @return AppliedPromotionEntity
     */
    public function mapPromotionDataToAppliedPromotion(PromotionDataInterface $promotion): AppliedPromotionEntity
    {
        $appliedPromotion = new AppliedPromotionEntity();
        $appliedPromotion->setPromotion($this->getManagedPromotion($promotion));
        $appliedPromotion->setPromotionName($promotion->getRule()->getName());
        $appliedPromotion->setSourcePromotionId($promotion->getId());
        $appliedPromotion->setConfigOptions($promotion->getDiscountConfiguration()->getOptions());
        $appliedPromotion->setType($promotion->getDiscountConfiguration()->getType());
        $appliedPromotion->setPromotionData($this->promotionNormalizer->normalize($promotion));

        return $appliedPromotion;
    }

    /**
     * @param AppliedPromotionEntity $appliedPromotionEntity
     * @return PromotionDataInterface
     */
    public function mapAppliedPromotionToPromotionData(
        AppliedPromotionEntity $appliedPromotionEntity
    ): PromotionDataInterface {
        /** @var AppliedPromotionData $appliedPromotion */
        $appliedPromotion = $this->promotionNormalizer->denormalize($appliedPromotionEntity->getPromotionData());

        $discountConfiguration = new DiscountConfiguration();
        $discountConfiguration->setType($appliedPromotionEntity->getType());
        $discountConfiguration->setOptions($appliedPromotionEntity->getConfigOptions());

        $appliedPromotion->setDiscountConfiguration($discountConfiguration);

        if ($appliedPromotionEntity->getAppliedCoupon()) {
            $appliedCoupon = $appliedPromotionEntity->getAppliedCoupon();
            $appliedPromotion->addCoupon($this->getCouponByAppliedCoupon($appliedCoupon));
        }

        return $appliedPromotion;
    }

    /**
     * @param PromotionDataInterface $promotion
     * @return object|PromotionDataInterface
     */
    private function getManagedPromotion(PromotionDataInterface $promotion)
    {
        if ($promotion instanceof Promotion) {
            return $promotion;
        }

        return $this->findById(Promotion::class, $promotion->getId());
    }

    /**
     * @param AppliedCoupon $appliedCoupon
     * @return Coupon
     */
    private function getCouponByAppliedCoupon(AppliedCoupon $appliedCoupon)
    {
        /** @var Coupon $coupon */
        $coupon = $this->findById(Coupon::class, $appliedCoupon->getSourceCouponId());

        if (!$coupon || $coupon->getCode() !== $appliedCoupon->getCouponCode()
            || !$coupon->getPromotion() || $coupon->getPromotion()->getId() !== $appliedCoupon->getSourcePromotionId()
        ) {
            $coupon = new Coupon();
            $coupon->setCode($appliedCoupon->getCouponCode());

            /** @var Promotion $promotion */
            $promotion = $this->getPromotion($appliedCoupon->getSourcePromotionId());
            $coupon->setPromotion($promotion);
        }

        return $coupon;
    }

    /**
     * @param int $id
     * @return Promotion
     */
    private function getPromotion($id)
    {
        $promotion = $this->findById(Promotion::class, $id);
        if (!$promotion) {
            $promotion = new Promotion();
            $property = new \ReflectionProperty(Promotion::class, 'id');
            $property->setAccessible(true);
            $property->setValue($promotion, $id);
        }

        return $promotion;
    }

    /**
     * @param string $class
     * @param int $id
     * @return object
     */
    private function findById($class, $id)
    {
        return $this->registry->getManagerForClass($class)->find($class, $id);
    }
}
