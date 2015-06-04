<?php

namespace OroB2B\Bundle\PricingBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class PriceProductAllowedUnits extends Constraint
{
    public $message = 'Unit "%unit%" is not supported for product "%product%".';

    public function validatedBy()
    {
        return 'orob2b_pricing_price_product_allowed_units_validator';
    }

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
