<?php

namespace Oro\Bundle\ShippingBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class UniqueProductUnitShippingOptions extends Constraint
{
    /**
     * @var string
     */
    public $message = 'oro.shipping.validators.product_unit_shipping_options.unique_entity.message';

    #[\Override]
    public function validatedBy(): string
    {
        return 'oro_shipping_unique_product_unit_shipping_options_validator';
    }
}
