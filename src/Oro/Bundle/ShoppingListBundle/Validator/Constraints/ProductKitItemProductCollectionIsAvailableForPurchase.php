<?php

namespace Oro\Bundle\ShoppingListBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Checks that collection of {@see ProductKitItemProduct} entities is available for purchase.
 */
class ProductKitItemProductCollectionIsAvailableForPurchase extends Constraint
{
    public const NO_AVAILABLE_PRODUCTS_ERROR = '754179da-ce10-44a3-81e1-c045b478cae5';

    protected static $errorNames = [
        self::NO_AVAILABLE_PRODUCTS_ERROR => 'NO_AVAILABLE_PRODUCTS_ERROR',
    ];

    public string $message
        = 'oro.shoppinglist.validators.product_kit_item_product_collection_is_available_for_purchase.message';

    public string $emptyMessage
        = 'oro.shoppinglist.validators.product_kit_item_product_collection_is_available_for_purchase.empty_message';
}
