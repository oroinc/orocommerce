<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * The constraint that can be used to validate that a product quantity is valid based on a product unit
 * of a product associated with the validating value.
 */
class QuantityUnitPrecision extends Constraint
{
    /** @var string */
    public $message = 'oro.product.productlineitem.quantity.invalid_precision';

    /**
     * The path to the quantity field.
     *
     * @var string
     */
    public string $path = '';

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
