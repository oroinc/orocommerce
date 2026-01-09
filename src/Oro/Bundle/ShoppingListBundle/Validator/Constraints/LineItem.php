<?php

namespace Oro\Bundle\ShoppingListBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Validation constraint for shopping list line items to prevent duplicates.
 *
 * This constraint ensures that a shopping list does not contain duplicate line items with the same product
 * and unit combination. It is applied during line item creation and updates to maintain data integrity
 * and prevent confusion when customers manage their shopping lists.
 * The validation is performed by checking the database for existing line items
 * with matching product, unit, and checksum values within the same shopping list.
 */
class LineItem extends Constraint
{
    public $message = 'oro.shoppinglist.lineitem.already_exists';

    #[\Override]
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }

    #[\Override]
    public function validatedBy(): string
    {
        return 'oro_shopping_list_line_item_validator';
    }
}
