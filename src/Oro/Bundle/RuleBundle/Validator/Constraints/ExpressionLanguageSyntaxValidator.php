<?php

namespace Oro\Bundle\RuleBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\ExpressionSyntaxValidator;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Decorates {@see SymfonyExpressionLanguageSyntaxValidator} and adds the following:
 * 1 Allows expression to be numeric or null.
 * 2 Skips validation is expression is empty string.
 * 3 Removes trailing spaces from expression.
 */
class ExpressionLanguageSyntaxValidator extends ConstraintValidator
{
    private ExpressionSyntaxValidator $innerExpressionLanguageValidator;

    public function __construct(ExpressionSyntaxValidator $innerExpressionLanguageValidator)
    {
        $this->innerExpressionLanguageValidator = $innerExpressionLanguageValidator;
    }

    #[\Override]
    public function validate($expression, Constraint $constraint): void
    {
        if (is_numeric($expression) || is_null($expression)) {
            $expression = (string) $expression;
        }

        $expression = trim($expression);

        if ($expression === '') {
            return;
        }

        $this->innerExpressionLanguageValidator->validate($expression, $constraint);
    }

    #[\Override]
    public function initialize(ExecutionContextInterface $context): void
    {
        $this->innerExpressionLanguageValidator->initialize($context);
    }
}
