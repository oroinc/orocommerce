<?php

namespace Oro\Bundle\ShoppingListBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

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
