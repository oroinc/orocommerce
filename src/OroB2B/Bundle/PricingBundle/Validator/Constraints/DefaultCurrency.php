<?php

namespace Oro\Bundle\PricingBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class DefaultCurrency extends Constraint
{
    /**
     * @var string
     */
    public $message = 'oro.pricing.validators.default_currency.message';

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return 'orob2b_pricing_default_currency_validator';
    }
}
