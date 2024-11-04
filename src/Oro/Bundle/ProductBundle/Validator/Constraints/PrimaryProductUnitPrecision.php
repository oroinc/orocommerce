<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Checks that a primary product unit precision exists in a collection of product unit precisions.
 */
class PrimaryProductUnitPrecision extends Constraint
{
    /** @var string */
    public $message = 'oro.product.unit_precisions_items.primary_precision_not_in_collection';

    #[\Override]
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
