<?php

namespace Oro\Bundle\PricingBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class PriceRuleRelationExpressions extends Constraint
{
    const ALIAS = 'oro_pricing.validator_constraints.price_rule_relation_expressions_validator';

    /**
     * @return string
     */
    public function validatedBy()
    {
        return self::ALIAS;
    }

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
