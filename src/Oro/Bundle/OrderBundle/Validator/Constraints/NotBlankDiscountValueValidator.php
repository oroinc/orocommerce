<?php

namespace Oro\Bundle\OrderBundle\Validator\Constraints;

use Oro\Bundle\OrderBundle\Entity\OrderDiscount;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates that the OrderDiscount entity has either "amount" or "percent" value.
 */
class NotBlankDiscountValueValidator extends ConstraintValidator
{
    /**
     * {@inheritDoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof NotBlankDiscountValue) {
            throw new UnexpectedTypeException($constraint, NotBlankDiscountValue::class);
        }

        if (null === $value) {
            return;
        }

        if (!$value instanceof OrderDiscount) {
            throw new UnexpectedTypeException($value, OrderDiscount::class);
        }

        switch ($value->getType()) {
            case OrderDiscount::TYPE_AMOUNT:
                if ($this->isBlank($value->getAmount())) {
                    $this->context->buildViolation($constraint->message)
                        ->atPath('amount')
                        ->addViolation();
                }
                break;
            case OrderDiscount::TYPE_PERCENT:
                if ($this->isBlank($value->getPercent())) {
                    $this->context->buildViolation($constraint->message)
                        ->atPath('percent')
                        ->addViolation();
                }
                break;
        }
    }

    /**
     * @param mixed $value
     *
     * @return bool
     */
    private function isBlank($value): bool
    {
        return
            null === $value
            || false === $value
            || (empty($value) && '0' != $value);
    }
}
