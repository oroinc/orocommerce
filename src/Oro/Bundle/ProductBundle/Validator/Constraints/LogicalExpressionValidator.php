<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Oro\Component\Expression\ExpressionParser;
use Oro\Component\Expression\Preprocessor\ExpressionPreprocessorInterface;
use Symfony\Component\ExpressionLanguage\SyntaxError;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

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

            if ($node) {
                if ($constraint->logicalExpressionsAllowed && !$node->isBoolean()) {
                    $this->context->addViolation($constraint->message);
                }
                if (!$constraint->logicalExpressionsAllowed && $node->isBoolean()) {
                    $this->context->addViolation($constraint->messageDisallowedLogicalExpression);
                }
            }
        } catch (SyntaxError $ex) {
            $this->context->addViolation($ex->getMessage());
        }
    }
}
