<?php

namespace Oro\Bundle\PricingBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Constraint for validating product price currencies.
 *
 * Ensures that product prices use valid currencies as defined in the system configuration.
 */
class ProductPriceCurrency extends Constraint
{
    /**
     * @var string
     */
    public $message = 'oro.pricing.validators.product_price.currency.message';

    #[\Override]
    public function validatedBy(): string
    {
        return 'oro_pricing_product_price_currency_validator';
    }

    #[\Override]
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
