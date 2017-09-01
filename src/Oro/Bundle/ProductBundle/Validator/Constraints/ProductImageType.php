<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class ProductImageType extends Constraint
{
    /**
     * @var string
     */
    public $message = 'oro.product.product_image_type.invalid_type';

    /**
     * {@inheritDoc}
     */
    public function validatedBy()
    {
        return ProductImageTypeValidator::ALIAS;
    }

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
