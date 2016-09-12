<?php

namespace Oro\Bundle\PricingBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class PriceListProductPricesCurrency extends Constraint
{
    /**
     * @var string
     */
    public $message = 'oro.pricing.validators.price_list.product_price_currency.message';

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return 'oro_pricing_price_list_product_prices_currency_validator';
    }

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
