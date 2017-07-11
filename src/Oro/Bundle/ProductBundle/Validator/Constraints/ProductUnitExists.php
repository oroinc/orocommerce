<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class ProductUnitExists extends Constraint
{
    /**
     * @var string
     */
    public $message = 'oro.product.frontend.quick_add.validation.invalid_unit';

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return ProductUnitExistsValidator::ALIAS;
    }

    /**
     * {@inheritDoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
