<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * Checks for at least one Product Kit Item
 * for Product with type {@see \Oro\Bundle\ProductBundle\Entity\Product::TYPE_KIT}.
 */
class NotEmptyProductKitItemsCollectionValidator extends ConstraintValidator
{
    public const MIN_KIT_ITEMS_VALUE = 1;

    /**
     * @param Product|null $value
     * @param Constraint $constraint
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof NotEmptyProductKitItemsCollection) {
            throw new UnexpectedTypeException($constraint, NotEmptyProductKitItemsCollection::class);
        }

        if (!$value instanceof Product) {
            throw new UnexpectedValueException($value, Product::class);
        }

        if ($value->getType() !== Product::TYPE_KIT) {
            return;
        }

        if ($value->getKitItems()->count() < self::MIN_KIT_ITEMS_VALUE) {
            $this->context
                ->buildViolation($constraint->message)
                ->atPath('kitItems')
                ->addViolation();
        }
    }
}
