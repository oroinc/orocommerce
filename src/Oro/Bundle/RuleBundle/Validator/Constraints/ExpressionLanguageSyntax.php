<?php

namespace Oro\Bundle\RuleBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraints\ExpressionSyntax;

/**
 * Validation constraint for expression language syntax.
 */
class ExpressionLanguageSyntax extends ExpressionSyntax
{
    public string $message = 'oro.rule.expression_language_syntax';

    #[\Override]
    public function validatedBy(): string
    {
        return 'oro_rule.validator_constraints.expression_language_syntax_validator';
    }
}
