<?php

namespace Oro\Bundle\PromotionBundle\CouponGeneration\Code;

use Oro\Bundle\PromotionBundle\CouponGeneration\Options\CodeGenerationOptions;

/**
 * This interface used to provide different implementation of Coupon code generators.
 */
interface CodeGeneratorInterface
{
    public function generateOne(CodeGenerationOptions $options): string;

    /**
     * @param CodeGenerationOptions $options
     * @param int $count requested number of codes to generate
     * @return array array of generated codes (could be less than requested number)
     */
    public function generateUnique(CodeGenerationOptions $options, int $count): array;
}
