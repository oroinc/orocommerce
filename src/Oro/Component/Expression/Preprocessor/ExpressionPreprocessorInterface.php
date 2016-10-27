<?php

namespace Oro\Component\Expression\Preprocessor;

interface ExpressionPreprocessorInterface
{
    /**
     * @param string $expression
     * @return string
     */
    public function process($expression);
}
