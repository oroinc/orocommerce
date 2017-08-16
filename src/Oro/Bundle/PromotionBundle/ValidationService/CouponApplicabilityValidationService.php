<?php

namespace Oro\Bundle\PromotionBundle\ValidationService;

use Oro\Bundle\PromotionBundle\Entity\Coupon;
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
     * @param CouponValidationService $couponValidationService
     * @param PromotionProvider $promotionProvider
     */
    public function __construct(
        CouponValidationService $couponValidationService,
        PromotionProvider $promotionProvider
    ) {
        $this->couponValidationService = $couponValidationService;
        $this->promotionProvider = $promotionProvider;
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

        if ($entity->getAppliedCoupons()->contains($coupon)) {
            return [self::MESSAGE_COUPON_ALREADY_ADDED];
        }

        if ($this->promotionProvider->isPromotionApplied($entity, $coupon->getPromotion())) {
            return [self::MESSAGE_PROMOTION_ALREADY_APPLIED];
        }

        $entity->addAppliedCoupon($coupon);
        if (!$this->promotionProvider->isPromotionApplicable($entity, $coupon->getPromotion())) {
            return [self::MESSAGE_PROMOTION_NOT_APPLICABLE];
        }

        return [];
    }
}
