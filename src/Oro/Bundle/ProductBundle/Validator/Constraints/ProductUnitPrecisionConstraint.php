<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Oro\Bundle\ProductBundle\Validator\ProductUnitPrecisionValidator;

use Symfony\Component\Validator\Constraint;

class ProductUnitPrecisionConstraint extends Constraint
{
    /** @var string */
    public $message = 'oro.product.unit_precision.duplicate_units';

    /**
     * {@inheritDoc}
     */
    public function validatedBy()
    {
        return ProductUnitPrecisionValidator::ALIAS;
    }

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::PROPERTY_CONSTRAINT;
    }
}
