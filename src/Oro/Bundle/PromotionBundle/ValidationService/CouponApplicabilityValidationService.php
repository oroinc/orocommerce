<?php

namespace Oro\Bundle\PromotionBundle\ValidationService;

use Oro\Bundle\PromotionBundle\Entity\AppliedCoupon;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\PromotionBundle\Provider\EntityCouponsProvider;
use Oro\Bundle\PromotionBundle\Provider\PromotionProvider;

/**
 * This class validates coupon for a given entity, it checks that coupon is valid, coupon is applicable to the entity
 * and was not added before to this entity.
 */
class CouponApplicabilityValidationService
{
    const MESSAGE_COUPON_ALREADY_ADDED = 'oro.promotion.coupon.violation.coupon_already_added';
    const MESSAGE_PROMOTION_ALREADY_APPLIED = 'oro.promotion.coupon.violation.coupon_promotion_already_applied';
    const MESSAGE_PROMOTION_NOT_APPLICABLE = 'oro.promotion.coupon.violation.coupon_promotion_not_applicable';

    /**
     * @var CouponValidationService
     */
    private $couponValidationService;

    /**
     * @var PromotionProvider
     */
    private $promotionProvider;


    /**
     * @var EntityCouponsProvider
     */
    private $entityCouponsProvider;

    /**
     * @param CouponValidationService $couponValidationService
     * @param PromotionProvider $promotionProvider
     * @param EntityCouponsProvider $entityCouponsProvider
     */
    public function __construct(
        CouponValidationService $couponValidationService,
        PromotionProvider $promotionProvider,
        EntityCouponsProvider $entityCouponsProvider
    ) {
        $this->couponValidationService = $couponValidationService;
        $this->promotionProvider = $promotionProvider;
        $this->entityCouponsProvider = $entityCouponsProvider;
    }

    /**
     * @param Coupon $coupon
     * @param object $entity
     * @return array
     */
    public function getViolations(Coupon $coupon, $entity): array
    {
        $violations = $this->couponValidationService->getViolations($coupon);

        if (!empty($violations)) {
            return $violations;
        }

        /** @var AppliedCoupon $appliedCoupon */
        foreach ($entity->getAppliedCoupons() as $appliedCoupon) {
            if ($appliedCoupon->getSourceCouponId() === $coupon->getId()) {
                return [self::MESSAGE_COUPON_ALREADY_ADDED];
            }
        }

        if ($this->promotionProvider->isPromotionApplied($entity, $coupon->getPromotion())) {
            return [self::MESSAGE_PROMOTION_ALREADY_APPLIED];
        }

        $appliedCoupon = $this->entityCouponsProvider->createAppliedCouponByCoupon($coupon);
        $entity->addAppliedCoupon($appliedCoupon);
        if (!$this->promotionProvider->isPromotionApplicable($entity, $coupon->getPromotion())) {
            return [self::MESSAGE_PROMOTION_NOT_APPLICABLE];
        }

        return [];
    }
}
