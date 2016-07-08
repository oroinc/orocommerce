<?php

namespace OroB2B\Bundle\PricingBundle\Validator\Constraints;

use Symfony\Component\ExpressionLanguage\SyntaxError;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use OroB2B\Bundle\PricingBundle\Provider\PriceRuleAttributeProvider;
use OroB2B\Bundle\PricingBundle\Expression\ExpressionParser;

abstract class AbstractDefinedAttributesValidator extends ConstraintValidator
{
    /**
     * @var ExpressionParser
     */
    protected $parser;

    /**
     * @var PriceRuleAttributeProvider
     */
    protected $attributeProvider;

    /**
     * @param ExpressionParser $parser
     * @param PriceRuleAttributeProvider $attributeProvider
     */
    public function __construct(ExpressionParser $parser, PriceRuleAttributeProvider $attributeProvider)
    {
        $this->parser = $parser;
        $this->attributeProvider = $attributeProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        try {
            $lexemesInfo = $this->parser->getUsedLexemes($value);
            foreach ($lexemesInfo as $class => $lexemes) {
                $supportedFields = $this->getSupportedAttributes($class);
                $unsupportedFields = array_diff($lexemes, array_keys($supportedFields));
                if (!empty($unsupportedFields)) {
                    throw new \Exception('Unsupported fields used');
                }
            }
        } catch (SyntaxError $ex) {
            $this->context->addViolation('orob2b.pricing.validators.product_price.syntax_error.message');
        } catch (\Exception $ex) {
            $this->context->addViolation($constraint->message);
        }
    }

    /**
     * @param string $className
     * @return array
     * @throws \Exception
     */
    abstract protected function getSupportedAttributes($className);
}
