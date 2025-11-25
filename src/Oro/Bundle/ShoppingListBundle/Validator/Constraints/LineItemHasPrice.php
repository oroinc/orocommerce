<?php

namespace Oro\Bundle\ShoppingListBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Validates that line item has price.
 */
class LineItemHasPrice extends Constraint
{
    public string $message
        = 'oro.shoppinglist.validators.line_item_has_price.message';

    #[\Override]
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
