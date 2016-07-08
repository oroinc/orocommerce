<?php

namespace OroB2B\Bundle\PricingBundle\Validator\Constraints;

use Symfony\Component\ExpressionLanguage\SyntaxError;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use OroB2B\Bundle\PricingBundle\Provider\PriceRuleAttributeProvider;
use OroB2B\Bundle\PricingBundle\Expression\ExpressionParser;

class DefinedRuleAttributesValidator extends AbstractDefinedAttributesValidator
{
    /**
     * {@inheritdoc}
     */
    protected function getSupportedAttributes($className)
    {
        return $this->attributeProvider->getAvailableRuleAttributes($className);
    }
}
