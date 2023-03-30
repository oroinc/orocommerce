<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Checks that product unit is within the intersection of products units of the {@see ProductKitItem} products.
 */
class ProductKitItemUnitAvailableForSpecifiedProducts extends Constraint
{
    public const PRODUCT_UNIT_NOT_ALLOWED = 'f9f89fea-2174-4d3d-af63-903cc2d6ea2b';

    protected static $errorNames = [
        self::PRODUCT_UNIT_NOT_ALLOWED => 'PRODUCT_UNIT_NOT_ALLOWED',
    ];

    public string $message = 'oro.product.productkititem.unit.available_for_all_specified_products';

    public function getTargets(): array
    {
        return [self::CLASS_CONSTRAINT];
    }
}
