<?php

namespace Oro\Bundle\OrderBundle\Validator\Constraints;

use Oro\Bundle\OrderBundle\Entity\OrderDiscount;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates that the order discount type is valid.
 */
class DiscountTypeValidator extends ConstraintValidator
{
    private const array VALID_TYPES = [OrderDiscount::TYPE_AMOUNT, OrderDiscount::TYPE_PERCENT];

    #[\Override]
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof DiscountType) {
            throw new UnexpectedTypeException($constraint, DiscountType::class);
        }

        if (null !== $value && !\is_string($value)) {
            return;
        }

        if (null === $value || !\in_array($value, self::VALID_TYPES, true)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('%valid_types%', implode(', ', self::VALID_TYPES))
                ->addViolation();
        }
    }
}
