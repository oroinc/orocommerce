<?php

namespace Oro\Bundle\PromotionBundle\ValidationService;

use Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface;
use Oro\Bundle\PromotionBundle\Entity\Coupon;

/**
 * Checks if coupon was applied to the same organization as checkout's or order's organization.
 */
class OrganizationCouponValidator implements CouponValidatorInterface
{
    /**
     * {@inheritDoc}
     */
    public function getViolation(Coupon $coupon, object $entity): ?string
    {
        if ($entity instanceof OrganizationAwareInterface
            && $entity->getOrganization()
            && $coupon->getOrganization()->getId() !== $entity->getOrganization()->getId()
        ) {
            return 'oro.promotion.coupon.violation.invalid_coupon_code';
        }

        return null;
    }
}
