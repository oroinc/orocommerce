<?php

namespace Oro\Bundle\RuleBundle\ExpressionLanguage;

use Symfony\Component\ExpressionLanguage\ExpressionFunction;

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
