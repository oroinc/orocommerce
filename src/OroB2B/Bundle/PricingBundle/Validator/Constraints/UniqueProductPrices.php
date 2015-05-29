<?php

namespace OroB2B\Bundle\PricingBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class UniqueProductPrices extends Constraint
{
    /**
     * @var string
     */
    public $message = 'All product prices should be unique.';

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return 'orob2b_pricing_unique_product_prices_validator';
    }
}
