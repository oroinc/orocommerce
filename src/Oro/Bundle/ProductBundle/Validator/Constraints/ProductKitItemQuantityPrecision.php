<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Checks that {@see ProductKitItem} quantity follows the precision.
 */
class ProductKitItemQuantityPrecision extends Constraint
{
    public const MAXIMUM_QUANTITY_PRECISION_ERROR = '7441d104-fcdc-40c2-b27a-27288c105fc3';
    public const MINIMUM_QUANTITY_PRECISION_ERROR = '0d2933e7-cf94-4a36-be37-813c0599e192';

    protected static $errorNames = [
        self::MINIMUM_QUANTITY_PRECISION_ERROR => 'MINIMUM_QUANTITY_PRECISION_ERROR',
        self::MAXIMUM_QUANTITY_PRECISION_ERROR => 'MAXIMUM_QUANTITY_PRECISION_ERROR',
    ];

    public string $minimumQuantityMessage = 'oro.product.productkititem.minimum_quantity.invalid_precision';

    public string $maximumQuantityMessage = 'oro.product.productkititem.maximum_quantity.invalid_precision';

    public function getTargets(): array
    {
        return [self::CLASS_CONSTRAINT];
    }
}
