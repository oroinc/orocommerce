<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Checks that each {@see ProductKitItem} in {@see Product::$kitItems} collection is owned by product kit.
 */
class ProductKitItemsOwnedByProductKit extends Constraint
{
    public const KIT_ITEM_IS_NOT_OWNED_ERROR = 'd7676f76-4eff-4879-945b-23779aa4bf68';

    protected static $errorNames = [
        self::KIT_ITEM_IS_NOT_OWNED_ERROR => 'KIT_ITEM_IS_NOT_OWNED_ERROR',
    ];

    public string $message = 'oro.product.kit_items.not_owned';

    public function getTargets(): array
    {
        return [self::CLASS_CONSTRAINT];
    }
}
