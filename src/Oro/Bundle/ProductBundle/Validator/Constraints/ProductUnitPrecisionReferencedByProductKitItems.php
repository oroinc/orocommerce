<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Checks if {@see ProductUnitPrecision::$unit} can be changed taking into account product kit items referencing it.
 */
class ProductUnitPrecisionReferencedByProductKitItems extends Constraint
{
    public const UNIT_PRECISION_CANNOT_BE_CHANGED = '12f3e6fa-25a0-4ac9-bcd0-08bd4d273733';

    protected static $errorNames = [
        self::UNIT_PRECISION_CANNOT_BE_CHANGED => 'UNIT_PRECISION_CANNOT_BE_CHANGED',
    ];

    public string $message = 'oro.product.productunit.unit.referenced_by_product_kits';

    public function getTargets(): array
    {
        return [self::CLASS_CONSTRAINT];
    }
}
