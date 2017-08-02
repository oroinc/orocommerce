<?php

namespace Oro\Bundle\PromotionBundle\CouponGeneration\Code;

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
    public function generate(CodeGenerationOptions $options): string;

    /**
     * @param CodeGenerationOptions $options
     * @param int $amount
     * @return array Indexed by code
     * @throws WrongAmountCodeGeneratorException
     */
    public function generateUnique(CodeGenerationOptions $options, int $amount): array;
}
