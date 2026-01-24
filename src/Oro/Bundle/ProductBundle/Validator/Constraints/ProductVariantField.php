<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Constraint to validate product variant field configurations.
 *
 * This constraint ensures that variant fields selected for configurable products are valid attributes
 * that can be used for product variation, preventing invalid variant field configurations.
 */
class ProductVariantField extends Constraint
{
    /** @var string */
    public $message = 'oro.product.product_variant_field.message';

    #[\Override]
    public function validatedBy(): string
    {
        return ProductVariantFieldValidator::ALIAS;
    }

    #[\Override]
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
