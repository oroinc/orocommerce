<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Constraint to validate product image type collections.
 *
 * This constraint ensures that collections of product image types meet type restrictions
 * and do not contain duplicate or invalid image type assignments.
 */
class ProductImageTypeCollection extends Constraint
{
    /**
     * @var string
     */
    public $message = 'oro.product.product_image_type.type_restriction';

    #[\Override]
    public function validatedBy(): string
    {
        return ProductImageTypeCollectionValidator::ALIAS;
    }
}
