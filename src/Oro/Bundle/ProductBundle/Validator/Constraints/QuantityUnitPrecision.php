<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class QuantityUnitPrecision extends Constraint
{
    /**
     * @var string
     */
    public $message = 'oro.product.frontend.quick_add.validation.invalid_precision';

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return QuantityUnitPrecisionValidator::ALIAS;
    }

    /**
     * {@inheritDoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
