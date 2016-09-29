<?php

namespace Oro\Bundle\PricingBundle\Expression\Preprocessor;

interface ExpressionPreprocessorInterface
{
    /**
     * @param string $expression
     * @return string
     */
    public function process($expression);
}
