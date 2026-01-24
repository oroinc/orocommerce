<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Constraint to validate that simple products used as variants have unique variant field combinations.
 *
 * This constraint ensures that when a simple product is added as a variant to a configurable product,
 * it has a unique combination of variant field values, even when some variant fields are empty.
 */
class EmptyVariantFieldInSimpleProductForVariantLinks extends Constraint
{
    public $message = 'oro.product.product_variant_field.unique_variant_links_when_empty_variant_field_in_simple';

    #[\Override]
    public function validatedBy(): string
    {
        return EmptyVariantFieldInSimpleProductForVariantLinksValidator::ALIAS;
    }

    #[\Override]
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
