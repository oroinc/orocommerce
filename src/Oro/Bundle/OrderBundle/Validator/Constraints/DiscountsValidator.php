<?php

namespace Oro\Bundle\OrderBundle\Validator\Constraints;

use Oro\Bundle\OrderBundle\Entity\Order;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates that the sum of all order discounts does not exceed the order grand total amount.
 */
class DiscountsValidator extends ConstraintValidator
{
    /**
     * {@inheritDoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof Discounts) {
            throw new UnexpectedTypeException($constraint, Discounts::class);
        }

        if (null === $value) {
            return;
        }

        if (!$value instanceof Order) {
            throw new UnexpectedTypeException($value, Order::class);
        }

        if ($value->getTotalDiscounts()
            && $value->getSubtotal()
            && $value->getSubtotal() < $value->getTotalDiscounts()->getValue()
        ) {
            $this->addSingleViolation($constraint);
        }
    }

    private function addSingleViolation(Discounts $constraint): void
    {
        $exists = false;
        /** @var ConstraintViolation $violation */
        foreach ($this->context->getViolations() as $violation) {
            if ($violation->getConstraint() === $constraint
                && $violation->getInvalidValue() === $this->context->getValue()
            ) {
                $exists = true;
                break;
            }
        }

        if (!$exists) {
            $this->context->buildViolation($constraint->errorMessage)
                ->atPath('totalDiscountsAmount')
                ->addViolation();
        }
    }
}
