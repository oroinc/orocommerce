<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class ProductImageTypeCollection extends Constraint
{
    /**
     * @var string
     */
    public $message = 'oro.product.product_image_type.type_restriction';

    /**
     * {@inheritDoc}
     */
    public function validatedBy()
    {
        return ProductImageTypeCollectionValidator::ALIAS;
    }
}
