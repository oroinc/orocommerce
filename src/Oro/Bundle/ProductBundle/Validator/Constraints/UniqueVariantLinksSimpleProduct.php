<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class UniqueVariantLinksSimpleProduct extends Constraint
{
    public $message = 'oro.product.product_variant_field.unique_variants_combination_simple_product.message';

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return UniqueVariantLinksSimpleProductValidator::ALIAS;
    }

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
