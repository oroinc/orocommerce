<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * The constraint that can be used to validate that a product quantity is valid based on a product unit
 * of a product associated with the validating value.
 */
class QuantityUnitPrecision extends Constraint
{
    public string $message = 'oro.product.productlineitem.quantity.invalid_precision';

    /** The path to the quantity field. */
    public string $path = '';

    #[\Override]
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
