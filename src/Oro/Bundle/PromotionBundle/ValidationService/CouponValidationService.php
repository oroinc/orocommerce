<?php

namespace Oro\Bundle\PromotionBundle\ValidationService;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\PromotionBundle\Manager\CouponUsageManager;

/**
 * Validates if given coupon can be applied.
 */
class CouponValidationService implements CouponValidatorInterface
{
    const MESSAGE_DISABLED = 'oro.promotion.coupon.violation.disabled';
    const MESSAGE_ABSENT_PROMOTION = 'oro.promotion.coupon.violation.absent_promotion';
    const MESSAGE_EXPIRED = 'oro.promotion.coupon.violation.expired';
    const MESSAGE_NOT_STARTED = 'oro.promotion.coupon.violation.not_started';
    const MESSAGE_USAGE_LIMIT_EXCEEDED = 'oro.promotion.coupon.violation.usage_limit_exceeded';
    const MESSAGE_USER_USAGE_LIMIT_EXCEEDED = 'oro.promotion.coupon.violation.customer_user_usage_limit_exceeded';

    private CouponUsageManager $couponUsageManager;

    public function __construct(CouponUsageManager $couponUsageManager)
    {
        $this->couponUsageManager = $couponUsageManager;
    }

    public function isValid(Coupon $coupon, CustomerUser $customerUser = null): bool
    {
        return !$this->getViolations($coupon, $customerUser);
    }

    #[\Override]
    public function getViolationMessages(Coupon $coupon, object $entity): array
    {
        return $this->getViolations($coupon, $entity->getCustomerUser());
    }

    public function getViolations(Coupon $coupon, CustomerUser $customerUser = null): array
    {
        $violations = [];

        if (!$coupon->isEnabled()) {
            $violations[] = self::MESSAGE_DISABLED;
        }

        if (!$coupon->getPromotion()) {
            $violations[] = self::MESSAGE_ABSENT_PROMOTION;
        }

        if ($this->isCouponNotStarted($coupon)) {
            $violations[] = self::MESSAGE_NOT_STARTED;
        }

        if ($this->isCouponExpired($coupon)) {
            $violations[] = self::MESSAGE_EXPIRED;
        }

        if ($this->isCouponUsageLimitExceeded($coupon)) {
            $violations[] = self::MESSAGE_USAGE_LIMIT_EXCEEDED;
        }

        if ($this->isCouponUsagePerCustomerUserLimitExceeded($coupon, $customerUser)) {
            $violations[] = self::MESSAGE_USER_USAGE_LIMIT_EXCEEDED;
        }

        return $violations;
    }

    private function isCouponNotStarted(Coupon $coupon): bool
    {
        return $coupon->getValidFrom() && $coupon->getValidFrom() > new \DateTime('now', new \DateTimeZone('UTC'));
    }

    private function isCouponExpired(Coupon $coupon): bool
    {
        return $coupon->getValidUntil() && $coupon->getValidUntil() < new \DateTime('now', new \DateTimeZone('UTC'));
    }

    private function isCouponUsageLimitExceeded(Coupon $coupon): bool
    {
        return $coupon->getUsesPerCoupon() !== null
            && $coupon->getUsesPerCoupon() <= $this->couponUsageManager->getCouponUsageCount($coupon);
    }

    private function isCouponUsagePerCustomerUserLimitExceeded(Coupon $coupon, CustomerUser $customerUser = null): bool
    {
        return $customerUser
            && $coupon->getUsesPerPerson() !== null
            && $coupon->getUsesPerPerson() <= $this->couponUsageManager
                ->getCouponUsageCountByCustomerUser($coupon, $customerUser);
    }
}
