<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Oro\Bundle\ProductBundle\Entity\ProductKitItemLabel;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * Checks that {@see ProductKitItemLabel} string not empty if not have fallback
 */
class NotBlankProductKitItemLabelStringValidator extends ConstraintValidator
{
    /**
     * @param ProductKitItemLabel $value
     * @param NotBlankProductKitItemLabelString $constraint
     * @return void
     */
    public function validate(mixed $value, Constraint $constraint)
    {
        if (!$constraint instanceof NotBlankProductKitItemLabelString) {
            throw new UnexpectedTypeException($constraint, ProductKitItemQuantityPrecision::class);
        }

        if (!$value instanceof ProductKitItemLabel) {
            throw new UnexpectedValueException($value, ProductKitItemLabel::class);
        }

        if ($value->getFallback()) {
            return;
        }

        if (!$value->getString()) {
            $this->context->buildViolation($constraint->message)
                ->atPath('string')
                ->addViolation();
        }
    }
}
