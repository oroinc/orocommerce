<?php

namespace Oro\Bundle\PricingBundle\Validator\Constraints;

use Oro\Bundle\PricingBundle\Expression\ExpressionParser;
use Symfony\Component\ExpressionLanguage\SyntaxError;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class CurrencyExpressionValidator extends ConstraintValidator
{
    /**
     * @var ExpressionParser
     */
    protected $parser;

    /**
     * @param ExpressionParser $parser
     */
    public function __construct(ExpressionParser $parser)
    {
        $this->parser = $parser;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if ($value === null || $value === '') {
            return;
        }

        try {
            $lexemesInfo = $this->parser->getUsedLexemes($value);
            foreach ($lexemesInfo as $class => $fields) {
                foreach ($fields as $field) {
                    if (false === strpos(strtolower($field), 'currency')) {
                        $this->context->addViolation(
                            $constraint->message,
                            ['%fieldName%' => $field]
                        );
                    }
                }
            }
        } catch (SyntaxError $ex) {
            $this->context->addViolation($ex->getMessage());
        }
    }
}
