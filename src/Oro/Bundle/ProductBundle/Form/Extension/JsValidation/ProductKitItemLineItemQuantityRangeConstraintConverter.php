<?php

namespace Oro\Bundle\ProductBundle\Form\Extension\JsValidation;

use Oro\Bundle\FormBundle\Form\Extension\JsValidation\ConstraintConverterInterface;
use Oro\Bundle\ProductBundle\Model\ProductKitItemAwareInterface;
use Oro\Bundle\ProductBundle\Validator\Constraints\ProductKitItemLineItemQuantityRange;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Range;

/**
 * Converts ProductKitItemLineItemQuantityRange constraint with Range constraint for JS validation.
 */
class ProductKitItemLineItemQuantityRangeConstraintConverter implements ConstraintConverterInterface
{
    #[\Override]
    public function supports(Constraint $constraint, ?FormInterface $form = null): bool
    {
        return $constraint instanceof ProductKitItemLineItemQuantityRange;
    }

    #[\Override]
    public function convertConstraint(Constraint $constraint, ?FormInterface $form = null): ?Constraint
    {
        /** @var ProductKitItemLineItemQuantityRange $constraint */

        if (null === $form) {
            return null;
        }
        $object = $form->getParent()?->getData();
        if (!$object instanceof ProductKitItemAwareInterface) {
            return null;
        }
        $kitItem = $object->getKitItem();
        if (null === $kitItem) {
            return null;
        }

        $minQuantity = $kitItem->getMinimumQuantity();
        $maxQuantity = $kitItem->getMaximumQuantity();
        if (null === $minQuantity && null === $maxQuantity) {
            return null;
        }

        $rangeConstraint = new Range(['min' => $minQuantity, 'max' => $maxQuantity]);
        if (null !== $constraint->notInRangeMessage) {
            $rangeConstraint->notInRangeMessage = $constraint->notInRangeMessage;
        }
        if (null !== $constraint->minMessage) {
            $rangeConstraint->minMessage = $constraint->minMessage;
        }
        if (null !== $constraint->maxMessage) {
            $rangeConstraint->maxMessage = $constraint->maxMessage;
        }

        return $rangeConstraint;
    }
}
