<?php

namespace Oro\Bundle\PricingBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Constraint for validating that prices exist for product units being removed.
 *
 * Ensures that product units with associated prices cannot be deleted without
 * first removing their price attribute prices.
 */
class PriceForProductUnitExists extends Constraint
{
    public const VALIDATOR = 'oro_pricing_price_for_product_unit_exists_validator';

    /**
     * @var string
     */
    public $message = 'oro.pricing.validators.price_for_product_unit_exists.message';

    #[\Override]
    public function validatedBy(): string
    {
        return self::VALIDATOR;
    }
}
