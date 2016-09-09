<?php

namespace Oro\Bundle\PricingBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class PriceRuleExpression extends Constraint
{
    /**
     * @var string
     */
    public $message = 'oro.pricing.validators.field_are_not_allowed.message';

    /**
     * @var bool
     */
    public $withRelations = false;

    /**
     * @var bool
     */
    public $numericOnly = false;

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return 'oro_pricing.validator_constraints.price_rule_expression_validator';
    }
}
