<?php

namespace OroB2B\Bundle\PricingBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class DefinedRuleAttributes extends Constraint
{
    /**
     * @var string
     */
    public $message = 'orob2b.pricing.validators.product_price.unknown_lexemes.message';

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return 'orob2b_pricing.validator_constraints.known_lexemes_validator';
    }
}
