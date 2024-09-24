<?php

namespace Oro\Bundle\ShoppingListBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Validates that {@see ProductKitItemProduct} has prices for {@see Product}.
 */
class ProductKitItemProductHasPrice extends Constraint
{
    public string $productHasNoPriceMessage
        = 'oro.shoppinglist.product_kit_is_available_for_purchase.product.has_price.message';

    #[\Override]
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
