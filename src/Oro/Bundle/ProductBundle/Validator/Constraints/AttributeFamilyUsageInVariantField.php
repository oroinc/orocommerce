<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Constraint to validate that an attribute family is not used in product variant fields.
 *
 * This constraint ensures that attribute families cannot be deleted or modified if they contain attributes
 * that are currently being used as variant fields in configurable products.
 */
class AttributeFamilyUsageInVariantField extends Constraint
{
    public $message = 'oro.product.attribute_family.used_in_product_variant_field.message';

    #[\Override]
    public function validatedBy(): string
    {
        return AttributeFamilyUsageInVariantFieldValidator::ALIAS;
    }

    #[\Override]
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
