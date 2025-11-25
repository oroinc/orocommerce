<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 *
 * Constraint ensures that either savedForLaterList or shoppingList is set, but not both.
 */
class OnlyOneRequiredList extends Constraint
{
    public string $message = 'oro.shoppinglist.lineitem.only_one_required_list.message';

    #[\Override]
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
