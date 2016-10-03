<?php

namespace Oro\Bundle\PricingBundle\Validator\Constraints;

use Oro\Bundle\PricingBundle\Expression\Preprocessor\ExpressionPreprocessorInterface;
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
     * @var ExpressionPreprocessorInterface
     */
    protected $preprocessor;

    /**
     * @param ExpressionParser $expressionParser
     * @param ExpressionPreprocessorInterface $preprocessor
     */
    public function __construct(ExpressionParser $expressionParser, ExpressionPreprocessorInterface $preprocessor)
    {
        $this->expressionParser = $expressionParser;
        $this->preprocessor = $preprocessor;
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
            $value = $this->preprocessor->process($value);
            $node = $this->expressionParser->parse($value);

            if ($node && !$node->isBoolean()) {
                $this->context->addViolation($constraint->message);
            }
        } catch (SyntaxError $ex) {
            $this->context->addViolation($constraint->message);
        }
    }
}
