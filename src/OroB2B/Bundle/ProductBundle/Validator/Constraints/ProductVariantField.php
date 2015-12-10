<?php

namespace OroB2B\Bundle\ProductBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class ProductVariantField extends Constraint
{
    /** @var string */
    public $message = 'orob2b.product.product_variant_field.message';

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return ProductVariantFieldValidator::ALIAS;
    }

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
