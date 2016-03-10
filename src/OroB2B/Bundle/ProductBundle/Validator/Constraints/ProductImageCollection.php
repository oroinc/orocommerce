<?php

namespace OroB2B\Bundle\ProductBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class ProductImageCollection extends Constraint
{
    /**
     * @var string
     */
    public $message = 'You cannot choose more than %maxNumber% images with type %type%';

    /**
     * {@inheritDoc}
     */
    public function validatedBy()
    {
        return ProductImageCollectionValidator::ALIAS;
    }
}
