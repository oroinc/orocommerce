<?php

namespace Oro\Bundle\PromotionBundle\CouponGeneration\Coupon;

use Oro\Bundle\PromotionBundle\CouponGeneration\Options\CouponGenerationOptions;

/**
 * Common interface for coupons mass generation
 */
interface CouponGeneratorInterface
{
    /**
     * Generate and insert into database set of coupons based on user defined generation parameters
     */
    public function generateAndSave(CouponGenerationOptions $options);
}
