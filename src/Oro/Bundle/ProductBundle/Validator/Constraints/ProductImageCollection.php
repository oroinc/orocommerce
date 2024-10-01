<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

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
