<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class ProductVariantField extends Constraint
{
    /** @var string */
    public $message = 'oro.product.product_variant_field.message';

    /**
     * {@inheritdoc}
     */
    public function validatedBy(): string
    {
        return ProductVariantFieldValidator::ALIAS;
    }

    /**
     * {@inheritdoc}
     */
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
