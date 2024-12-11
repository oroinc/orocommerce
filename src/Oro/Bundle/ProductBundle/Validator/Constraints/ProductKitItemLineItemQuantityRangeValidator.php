<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Oro\Bundle\ProductBundle\Model\ProductKitItemAwareInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Constraints\RangeValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * Validates that product kit item line item quantity is in the range specified in the product kit.
 */
class ProductKitItemLineItemQuantityRangeValidator extends RangeValidator
{
    #[\Override]
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ProductKitItemLineItemQuantityRange) {
            throw new UnexpectedTypeException($constraint, ProductKitItemLineItemQuantityRange::class);
        }

        if (null === $value) {
            return;
        }

        $object = $this->context->getObject();
        if (!$object instanceof ProductKitItemAwareInterface) {
            throw new UnexpectedValueException($object, ProductKitItemAwareInterface::class);
        }

        $kitItem = $object->getKitItem();
        if (null === $kitItem) {
            return;
        }

        $rangeConstraint = new Range([
            'minPropertyPath' => 'kitItem.minimumQuantity',
            'maxPropertyPath' => 'kitItem.maximumQuantity'
        ]);
        if (null !== $constraint->notInRangeMessage) {
            $rangeConstraint->notInRangeMessage = $constraint->notInRangeMessage;
        }
        if (null !== $constraint->minMessage) {
            $rangeConstraint->minMessage = $constraint->minMessage;
        }
        if (null !== $constraint->maxMessage) {
            $rangeConstraint->maxMessage = $constraint->maxMessage;
        }
        parent::validate($value, $rangeConstraint);
    }
}
