<?php

namespace OroB2B\Bundle\ProductBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class ProductImageCollection extends Constraint
{
    /**
     * @var string
     */
    public $message = 'orob2b.product.product_image.type_restriction';

    /**
     * {@inheritDoc}
     */
    public function validatedBy()
    {
        return ProductImageCollectionValidator::ALIAS;
    }
}
