<?php

namespace OroB2B\Bundle\PricingBundle\Validator\Constraints;

class PriceRuleConditionalExpressionValidator extends AbstractPriceRuleExpressionValidator
{
    /**
     * {@inheritdoc}
     */
    protected function getSupportedFields($className)
    {
        return $this->priceRuleFieldsProvider->getFields($className, true);
    }
}
