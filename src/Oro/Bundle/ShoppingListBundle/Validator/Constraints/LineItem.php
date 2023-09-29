<?php

namespace Oro\Bundle\ShoppingListBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class LineItem extends Constraint
{
    public $message = 'oro.shoppinglist.lineitem.already_exists';

    /**
     * {@inheritDoc}
     */
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }

    /**
     * {@inheritDoc}
     */
    public function validatedBy(): string
    {
        return 'oro_shopping_list_line_item_validator';
    }
}
