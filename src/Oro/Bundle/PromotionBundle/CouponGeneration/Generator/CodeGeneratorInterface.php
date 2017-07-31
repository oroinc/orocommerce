<?php

namespace Oro\Bundle\PromotionBundle\CouponGeneration\Generator;

use Oro\Bundle\PromotionBundle\CouponGeneration\Options\CodeGenerationOptions;

/**
 * This interface used to provide different implementation of Coupon code generators.
 */
interface CodeGeneratorInterface
{
    /**
     * @param CodeGenerationOptions $options
     * @return string
     */
    public function generate(CodeGenerationOptions $options);
}
