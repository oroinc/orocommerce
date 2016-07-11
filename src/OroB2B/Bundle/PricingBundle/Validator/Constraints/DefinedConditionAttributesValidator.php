<?php

namespace OroB2B\Bundle\PricingBundle\Validator\Constraints;

class DefinedConditionAttributesValidator extends AbstractDefinedAttributesValidator
{
    /**
     * {@inheritdoc}
     */
    protected function getSupportedAttributes($className)
    {
        return $this->attributeProvider->getAvailableConditionAttributes($className);
    }
}
