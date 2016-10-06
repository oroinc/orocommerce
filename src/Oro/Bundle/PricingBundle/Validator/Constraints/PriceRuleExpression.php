<?php

namespace Oro\Bundle\PricingBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class PriceRuleExpression extends Constraint
{
    /**
     * @var string
     */
    public $message = 'oro.pricing.validators.field_is_not_allowed.message';

    /**
     * @var string
     */
    public $messageAs = 'oro.pricing.validators.field_is_not_allowed_as.message';

    /**
     * @var string
     */
    public $divisionByZeroMessage = 'oro.pricing.validators.division_by_zero.message';

    /**
     * @var bool
     */
    public $withRelations = false;

    /**
     * @var bool
     */
    public $numericOnly = false;

    /**
     * @var array
     */
    public $allowedFields = [];

    /**
     * @var string
     */
    public $fieldLabel = null;

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return 'oro_pricing.validator_constraints.price_rule_expression_validator';
    }
}
