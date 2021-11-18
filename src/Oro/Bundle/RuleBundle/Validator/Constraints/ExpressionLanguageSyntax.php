<?php

namespace Oro\Bundle\RuleBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraints\ExpressionLanguageSyntax as SymfonyExpressionLanguageSyntax;

/**
 * Validation constraint for expression language syntax.
 */
class ExpressionLanguageSyntax extends SymfonyExpressionLanguageSyntax
{
    public $message = 'oro.rule.expression_language_syntax';

    public function validatedBy(): string
    {
        return 'oro_rule.validator_constraints.expression_language_syntax_validator';
    }
}
