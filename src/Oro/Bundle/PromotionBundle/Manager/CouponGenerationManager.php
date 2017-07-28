<?php

namespace Oro\Bundle\PromotionBundle\Manager;

use Oro\Bundle\PromotionBundle\CouponGeneration\Options\CouponGenerationOptions;
use Oro\Bundle\PromotionBundle\CouponGeneration\Generator\CodeGeneratorInterface;
use Oro\Bundle\PromotionBundle\CouponGeneration\Inserter\CouponInserterInterface;

/**
 * Manage Coupon Generation Services architecture and functional
 */
class CouponGenerationManager
{
    /**
     * @var CodeGeneratorInterface
     */
    protected $couponGenerator;

    /**
     * @var CouponInserterInterface
     */
    protected $couponInserter;

    /**
     * @param CodeGeneratorInterface $couponGenerator
     * @param CouponInserterInterface $couponInserter
     */
    public function __construct(CodeGeneratorInterface $couponGenerator, CouponInserterInterface $couponInserter)
    {
        $this->couponGenerator = $couponGenerator;
        $this->couponInserter = $couponInserter;
    }

    /**
     * Generate set of coupons based on user defined generation parameters
     *
     * @param CouponGenerationOptions $couponGenerationOptions
     */
    public function generateCoupons(CouponGenerationOptions $couponGenerationOptions)
    {
    }
}
