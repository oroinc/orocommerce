<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class ProductImage extends Constraint
{
    /**
     * @var string
     */
    public $message = 'oro.product.product_image.not_blank';

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
