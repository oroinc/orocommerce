<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Constraint to validate product image collections.
 *
 * This constraint ensures that product image collections meet type restrictions
 * and other requirements for proper image management within products.
 */
class ProductImageCollection extends Constraint
{
    /**
     * @var string
     */
    public $message = 'oro.product.product_image.type_restriction';

    #[\Override]
    public function validatedBy(): string
    {
        return ProductImageCollectionValidator::ALIAS;
    }
}
