<?php

namespace Oro\Component\Expression\Preprocessor;

/**
 * Defines the contract for expression preprocessors.
 *
 * Preprocessors transform expression strings before they are parsed, allowing for
 * expression normalization, substitution, and other transformations.
 */
interface ExpressionPreprocessorInterface
{
    /**
     * @param string $expression
     * @return string
     */
    public function process($expression);
}
