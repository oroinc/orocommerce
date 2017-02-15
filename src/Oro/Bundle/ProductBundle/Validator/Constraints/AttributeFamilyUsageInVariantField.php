<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class AttributeFamilyUsageInVariantField extends Constraint
{
    public $message = 'oro.product.attribute_family.used_in_product_variant_field.message';

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return AttributeFamilyUsageInVariantFieldValidator::ALIAS;
    }

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
