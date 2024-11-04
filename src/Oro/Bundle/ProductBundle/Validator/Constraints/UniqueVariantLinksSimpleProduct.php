<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

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
