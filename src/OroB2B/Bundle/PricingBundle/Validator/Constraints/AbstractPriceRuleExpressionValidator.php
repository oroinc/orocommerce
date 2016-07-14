<?php

namespace OroB2B\Bundle\PricingBundle\Validator\Constraints;

use Symfony\Component\ExpressionLanguage\SyntaxError;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use OroB2B\Bundle\PricingBundle\Provider\PriceRuleFieldsProvider;
use OroB2B\Bundle\PricingBundle\Expression\ExpressionParser;

abstract class AbstractPriceRuleExpressionValidator extends ConstraintValidator
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
                $supportedFields = $this->getSupportedFields($class);
                // Add possibility lexemes without fields
                array_push($supportedFields, null);
                $unsupportedFields = array_diff($fields, $supportedFields);
                if (!empty($unsupportedFields)) {
                    throw new \Exception('Unsupported fields used');
                }
            }
        } catch (SyntaxError $ex) {
            $this->context->addViolation($ex->getMessage());
        } catch (\Exception $ex) {
            $this->context->addViolation($ex->getMessage());
        }
    }

    /**
     * @param string $className
     * @return array
     * @throws \Exception
     */
    abstract protected function getSupportedFields($className);
}
