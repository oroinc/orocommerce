<?php

namespace Oro\Bundle\PricingBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class PriceForProductUnitExists extends Constraint
{
    const VALIDATOR = 'orob2b_pricing_price_for_product_unit_exists_validator';

    /**
     * @var string
     */
    public $message = 'oro.pricing.validators.price_for_product_unit_exists.message';

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return self::VALIDATOR;
    }
}
