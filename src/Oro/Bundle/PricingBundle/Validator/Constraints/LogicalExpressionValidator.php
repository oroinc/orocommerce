<?php

namespace Oro\Bundle\PricingBundle\Validator\Constraints;

use Symfony\Component\ExpressionLanguage\SyntaxError;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use Oro\Bundle\PricingBundle\Expression\ExpressionParser;

class LogicalExpressionValidator extends ConstraintValidator
{
    /**
     * @var ExpressionParser
     */
    protected $expressionParser;

    /**
     * @param ExpressionParser $expressionParser
     */
    public function __construct(ExpressionParser $expressionParser)
    {
        $this->expressionParser = $expressionParser;
    }

    /**
     * @param string $value
     * @param LogicalExpression $constraint
     *
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        try {
            $node = $this->expressionParser->parse($value);

            if ($node && !$node->isBoolean()) {
                $this->context->addViolation($constraint->message);
            }
        } catch (SyntaxError $ex) {
            $this->context->addViolation($constraint->message);
        }
    }
}
