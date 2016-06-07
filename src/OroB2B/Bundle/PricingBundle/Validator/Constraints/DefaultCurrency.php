<?php

namespace OroB2B\Bundle\PricingBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class DefaultCurrency extends Constraint
{
    /**
     * @var string
     */
    public $message = 'orob2b.pricing.validators.default_currency.message';

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return 'orob2b_pricing_default_currency_validator';
    }
}
