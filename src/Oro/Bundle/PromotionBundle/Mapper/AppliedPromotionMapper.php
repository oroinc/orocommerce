<?php

namespace Oro\Bundle\PromotionBundle\Mapper;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PromotionBundle\Entity\AppliedCoupon;
use Oro\Bundle\PromotionBundle\Entity\AppliedPromotion;
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

    public function __construct(ManagerRegistry $registry, NormalizerInterface $normalizer)
    {
        $this->registry = $registry;
        $this->promotionNormalizer = $normalizer;
    }

    /**
     * @param AppliedPromotion $appliedPromotion
     * @param PromotionDataInterface $promotion
     * @param Order|object $order
     */
    public function mapPromotionDataToAppliedPromotion(
        AppliedPromotion $appliedPromotion,
        PromotionDataInterface $promotion,
        Order $order
    ) {
        $appliedPromotion->setOrder($order);
        $appliedPromotion->setPromotionName($promotion->getRule()->getName());
        $appliedPromotion->setSourcePromotionId($promotion->getId());
        $appliedPromotion->setConfigOptions($promotion->getDiscountConfiguration()->getOptions());
        $appliedPromotion->setType($promotion->getDiscountConfiguration()->getType());
        $appliedPromotion->setPromotionData($this->promotionNormalizer->normalize($promotion));
        $appliedPromotion->setAppliedCoupon($this->getAppliedCoupon($order->getAppliedCoupons(), $promotion));
    }

    public function mapAppliedPromotionToPromotionData(AppliedPromotion $appliedPromotion): PromotionDataInterface
    {
        /** @var AppliedPromotionData $appliedPromotionData */
        $appliedPromotionData = $this->promotionNormalizer->denormalize($appliedPromotion->getPromotionData());

        $discountConfiguration = new DiscountConfiguration();
        $discountConfiguration->setType($appliedPromotion->getType());
        $discountConfiguration->setOptions($appliedPromotion->getConfigOptions());

        $appliedPromotionData->setDiscountConfiguration($discountConfiguration);

        if ($appliedPromotion->getAppliedCoupon()) {
            $appliedCoupon = $appliedPromotion->getAppliedCoupon();
            $appliedPromotionData->addCoupon($this->getCouponByAppliedCoupon($appliedCoupon));
        }

        return $appliedPromotionData;
    }

    /**
     * @param Collection $appliedCoupons
     * @param PromotionDataInterface $promotion
     * @return AppliedCoupon|null
     */
    private function getAppliedCoupon(Collection $appliedCoupons, PromotionDataInterface $promotion)
    {
        if ($appliedCoupons->isEmpty()) {
            return null;
        }

        $appliedCouponCodes = $appliedCoupons->map(
            function (AppliedCoupon $coupon) {
                return $coupon->getCouponCode();
            }
        )->toArray();

        $criteria = Criteria::create();
        $criteria->where(Criteria::expr()->in('code', $appliedCouponCodes));

        $coupon = $promotion->getCoupons()->matching($criteria)->first();
        if ($coupon instanceof Coupon) {
            $filteredCoupons = $appliedCoupons->filter(
                function (AppliedCoupon $appliedCoupon) use ($coupon) {
                    return $appliedCoupon->getCouponCode() === $coupon->getCode();
                }
            );

            return $filteredCoupons->first();
        }

        return null;
    }

    /**
     * @param AppliedCoupon $appliedCoupon
     * @return Coupon
     */
    private function getCouponByAppliedCoupon(AppliedCoupon $appliedCoupon)
    {
        /** @var Coupon $coupon */
        $coupon = $this->findById(Coupon::class, $appliedCoupon->getSourceCouponId());

        if (!$coupon
            || !$coupon->getPromotion()
            || $coupon->getCode() !== $appliedCoupon->getCouponCode()
            || $coupon->getPromotion()->getId() !== $appliedCoupon->getSourcePromotionId()
        ) {
            $coupon = new Coupon();
            $coupon->setCode($appliedCoupon->getCouponCode());

            /** @var Promotion $promotion */
            $promotion = $this->getPromotion($appliedCoupon->getSourcePromotionId());
            $promotion->addCoupon($coupon);
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
