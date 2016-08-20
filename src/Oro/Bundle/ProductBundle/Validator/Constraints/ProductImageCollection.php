<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class ProductImageCollection extends Constraint
{
    /**
     * @var string
     */
    public $message = 'oro.product.product_image.type_restriction';

    /**
     * {@inheritDoc}
     */
    public function validatedBy()
    {
        return ProductImageCollectionValidator::ALIAS;
    }
}
