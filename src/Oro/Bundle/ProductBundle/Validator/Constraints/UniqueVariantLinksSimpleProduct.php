<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Constraint to validate that simple products have unique variant field combinations.
 *
 * This constraint ensures that when simple products are used as variants in configurable products,
 * each variant has a unique combination of variant field values, preventing duplicate variants.
 */
class UniqueVariantLinksSimpleProduct extends Constraint
{
    public $message = 'oro.product.product_variant_field.unique_variants_combination_simple_product.message';

    #[\Override]
    public function validatedBy(): string
    {
        return UniqueVariantLinksSimpleProductValidator::ALIAS;
    }

    #[\Override]
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
