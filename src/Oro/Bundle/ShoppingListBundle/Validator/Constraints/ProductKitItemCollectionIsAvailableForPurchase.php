<?php

namespace Oro\Bundle\ShoppingListBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Checks that collection of {@see ProductKitItem} entities is available for purchase.
 */
class ProductKitItemCollectionIsAvailableForPurchase extends Constraint
{
    public const REQUIRED_KIT_ITEM_NOT_AVAILABLE_ERROR = '3d146742-8db0-49eb-a97a-13e43f4ff990';
    public const NO_AVAILABLE_KIT_ITEMS_ERROR = 'b8db1d07-8ee5-40f7-a913-74feca94b71b';

    protected static $errorNames = [
        self::REQUIRED_KIT_ITEM_NOT_AVAILABLE_ERROR => 'REQUIRED_KIT_ITEM_NOT_AVAILABLE_ERROR',
        self::NO_AVAILABLE_KIT_ITEMS_ERROR => 'NO_AVAILABLE_KIT_ITEMS_ERROR',
    ];

    public string $requiredKitItemNotAvailableMessage
        = 'oro.shoppinglist.validators.product_kit_item_collection_is_available_for_purchase.required_not_available';
    public string $noAvailableKitItemsMessage
        = 'oro.shoppinglist.validators.product_kit_item_collection_is_available_for_purchase.no_available';
}
