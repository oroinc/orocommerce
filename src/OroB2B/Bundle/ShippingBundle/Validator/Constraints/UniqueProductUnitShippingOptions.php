<?php

namespace OroB2B\Bundle\ShippingBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class UniqueProductUnitShippingOptions extends Constraint
{
    /**
     * @var string
     */
    public $message = 'orob2b.shipping.validators.product_unit_shipping_options.unique_entity.message';

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return 'orob2b_shipping_unique_product_unit_shipping_options_validator';
    }
}
