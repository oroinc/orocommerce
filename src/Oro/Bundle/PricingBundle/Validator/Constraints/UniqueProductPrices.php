<?php

namespace Oro\Bundle\PricingBundle\Validator\Constraints;

use Attribute;
use Symfony\Component\Validator\Constraint;

/**
 * UniqueProductPrices constraint
 *
 * @Annotation
 */
#[Attribute]
class UniqueProductPrices extends Constraint
{
    /**
     * @var string
     */
    public $message = 'oro.pricing.validators.product_price.unique_entity.message';

    /**
     * {@inheritdoc}
     */
    public function validatedBy(): string
    {
        return 'oro_pricing_unique_product_prices_validator';
    }
}
