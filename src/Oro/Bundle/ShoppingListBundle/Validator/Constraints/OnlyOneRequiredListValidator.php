<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\Validator\Constraints;

use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * Checks that one and only one of `shoppingList` or `savedForLaterShoppingList`
 * is not null. Adds a violation if both are null or both are set.
 */
class OnlyOneRequiredListValidator extends ConstraintValidator
{
    #[\Override]
    public function validate($value, Constraint $constraint): void
    {
        if (!$value instanceof LineItem) {
            throw new UnexpectedValueException($value, LineItem::class);
        }

        if (!$constraint instanceof OnlyOneRequiredList) {
            throw new UnexpectedTypeException($constraint, OnlyOneRequiredList::class);
        }

        if (!$this->hasExactlyOneListSet($value)) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }

    private function hasExactlyOneListSet(LineItem $value): bool
    {
        return ($value->getShoppingList() !== null) xor ($value->getSavedForLaterList() !== null);
    }
}
