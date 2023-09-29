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
    public function validatedBy(): string
    {
        return ProductImageValidator::ALIAS;
    }

    /**
     * {@inheritdoc}
     */
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
