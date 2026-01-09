<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Constraint to validate that a product image has an associated image file.
 *
 * This constraint ensures that product image entities are not empty and have a valid image file
 * attached before being saved.
 */
class ProductImage extends Constraint
{
    /**
     * @var string
     */
    public $message = 'oro.product.product_image.not_blank';

    #[\Override]
    public function validatedBy(): string
    {
        return ProductImageValidator::ALIAS;
    }

    #[\Override]
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
