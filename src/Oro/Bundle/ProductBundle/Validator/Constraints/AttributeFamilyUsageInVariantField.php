<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

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
