<?php

namespace Oro\Bundle\OrderBundle\Validator\Constraints;

use Oro\Bundle\OrderBundle\Entity\Order;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\ConstraintViolation;

class DiscountsValidator extends ConstraintValidator
{
    /**
     * @param Order|object $value
     * @param Discounts    $constraint
     *
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (null === $value) {
            return;
        }

        if (!$value instanceof Order) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Value must be instance of "%s", "%s" given',
                    'Oro\Bundle\OrderBundle\Entity\Order',
                    is_object($value) ? get_class($value) : gettype($value)
                )
            );
        }

        if ($value->getTotalDiscounts()
            && $value->getSubtotal()
            && $value->getSubtotal() < $value->getTotalDiscounts()->getValue()
        ) {
            $this->addSingleViolation($constraint);
        }
    }

    /**
     * @param Constraint $constraint
     */
    private function addSingleViolation(Constraint $constraint)
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
