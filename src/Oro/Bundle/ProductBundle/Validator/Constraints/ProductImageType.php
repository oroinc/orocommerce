<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Constraint to validate individual product image types.
 *
 * This constraint validates that image types assigned to product images are valid
 * and do not already exist for the same image, preventing duplicate type assignments.
 */
class ProductImageType extends Constraint
{
    /**
     * @var string
     */
    public $invalid_type_message = 'oro.product.product_image_type.invalid_type';

    /**
     * @var string
     */
    public $already_exists_message = 'oro.product.product_image_type.already_exists';

    #[\Override]
    public function validatedBy(): string
    {
        return ProductImageTypeValidator::ALIAS;
    }

    #[\Override]
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
