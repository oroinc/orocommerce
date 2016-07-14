<?php

namespace OroB2B\Bundle\ProductBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class ProductImage extends Constraint
{
    /**
     * @var string
     */
    public $message = 'orob2b.product.product_image.not_blank';

    /**
     * {@inheritDoc}
     */
    public function validatedBy()
    {
        return ProductImageValidator::ALIAS;
    }

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
