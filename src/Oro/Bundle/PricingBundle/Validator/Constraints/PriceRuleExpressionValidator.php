<?php

namespace Oro\Bundle\PricingBundle\Validator\Constraints;

use Symfony\Component\ExpressionLanguage\SyntaxError;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use Oro\Bundle\PricingBundle\Provider\PriceRuleFieldsProvider;
use Oro\Bundle\PricingBundle\Expression\ExpressionParser;

class PriceRuleExpressionValidator extends ConstraintValidator
{
    /**
     * @var ExpressionParser
     */
    protected $parser;

    /**
     * @var PriceRuleFieldsProvider
     */
    protected $priceRuleFieldsProvider;

    /**
     * @param ExpressionParser $parser
     * @param PriceRuleFieldsProvider $priceRuleFieldsProvider
     */
    public function __construct(ExpressionParser $parser, PriceRuleFieldsProvider $priceRuleFieldsProvider)
    {
        $this->parser = $parser;
        $this->priceRuleFieldsProvider = $priceRuleFieldsProvider;
    }

    /**
     * @param string $value
     * @param PriceRuleExpression $constraint
     *
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if ($value === null || $value === '') {
            return;
        }
        try {
            $unsupportedFields = [];
            $lexemesInfo = $this->parser->getUsedLexemes($value);
            foreach ($lexemesInfo as $class => $fields) {
                try {
                    $supportedFields = $this->priceRuleFieldsProvider->getFields(
                        $class,
                        $constraint->numericOnly,
                        $constraint->withRelations
                    );
                    // Add possibility lexemes without fields
                    $supportedFields[] = null;
                    $unsupportedFields = array_merge($unsupportedFields, array_diff($fields, $supportedFields));
                } catch (\InvalidArgumentException $ex) {
                    if (strpos($class, '::') !== false) {
                        $relationInfo = explode('::', $class);
                        $unsupportedFields[] = $relationInfo[1];
                    }
                }
            }
            if (count($unsupportedFields) > 0) {
                foreach ($unsupportedFields as $invalidField) {
                    $this->context->addViolation(
                        $constraint->message,
                        ['%fieldName%' => $invalidField]
                    );
                }
            }
        } catch (SyntaxError $ex) {
            $this->context->addViolation($ex->getMessage());
        }
    }
}
