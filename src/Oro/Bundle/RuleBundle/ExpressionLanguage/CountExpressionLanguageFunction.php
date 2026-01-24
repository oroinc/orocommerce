<?php

namespace Oro\Bundle\RuleBundle\ExpressionLanguage;

use Symfony\Component\ExpressionLanguage\ExpressionFunction;

/**
 * Provides a `count()` function for use in rule expressions.
 *
 * This class extends Symfony's {@see ExpressionFunction} to add a `count()` function that can be used
 * within rule expressions evaluated by the expression language. It allows rule developers to count elements in arrays
 * or collections as part of their rule conditions. The function generates the appropriate SQL/expression syntax
 * for compilation and provides the runtime evaluation logic using PHP's native `count()` function.
 */
class CountExpressionLanguageFunction extends ExpressionFunction
{
    const FUNCTION_NAME = 'count';

    public function __construct()
    {
        parent::__construct(
            self::FUNCTION_NAME,
            function ($field) {
                return sprintf('%s(%s)', self::FUNCTION_NAME, $field);
            },
            function ($arguments, $field) {
                return count($field);
            }
        );
    }
}
