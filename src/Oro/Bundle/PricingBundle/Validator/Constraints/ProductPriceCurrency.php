<?php

namespace Oro\Bundle\PricingBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

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
