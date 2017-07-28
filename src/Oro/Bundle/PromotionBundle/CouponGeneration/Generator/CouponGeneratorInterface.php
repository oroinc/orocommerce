<?php

namespace Oro\Bundle\PromotionBundle\CouponGeneration\Generator;

use Oro\Bundle\PromotionBundle\CouponGeneration\Options\CodeGenerationOptions;

interface CouponGeneratorInterface
{
    /**
     * @param CodeGenerationOptions $options
     * @return string
     * @throws BadOptionsException
     */
    public function generate(CodeGenerationOptions $options);
}
