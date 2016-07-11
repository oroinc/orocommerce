<?php

namespace OroB2B\Bundle\PricingBundle\Validator\Constraints;

class DefinedRuleAttributesValidator extends AbstractDefinedAttributesValidator
{
    /**
     * {@inheritdoc}
     */
    protected function getSupportedAttributes($className)
    {
        return $this->attributeProvider->getAvailableConditionAttributes($className);
    }
}
