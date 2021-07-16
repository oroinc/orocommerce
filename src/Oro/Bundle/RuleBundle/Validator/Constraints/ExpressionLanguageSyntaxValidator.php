<?php

namespace Oro\Bundle\RuleBundle\Validator\Constraints;

use Oro\Component\ExpressionLanguage\BasicExpressionLanguageValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ExpressionLanguageSyntaxValidator extends ConstraintValidator
{
    /**
     * @var BasicExpressionLanguageValidator
     */
    private $basicExpressionLanguageValidator;

    public function __construct(BasicExpressionLanguageValidator $basicExpressionLanguageValidator)
    {
        $this->basicExpressionLanguageValidator = $basicExpressionLanguageValidator;
    }

    /**
     * @param string     $expression
     * @param Constraint $constraint
     */
    public function validate($expression, Constraint $constraint)
    {
        if ($expression) {
            $validateResult = $this->basicExpressionLanguageValidator->validate($expression);
            if ($validateResult) {
                $this->context->addViolation($validateResult);
            }
        }
    }
}
