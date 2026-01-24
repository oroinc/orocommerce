<?php

namespace Oro\Bundle\ShippingBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Constraint that validates uniqueness of product units in shipping options.
 *
 * This constraint ensures that each product unit appears only once in a collection of shipping options,
 * preventing duplicate configurations for the same unit.
 */
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
