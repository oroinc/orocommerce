<?php

namespace Oro\Bundle\OrderBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use Oro\Bundle\OrderBundle\Entity\Order;

class DiscountsValidator extends ConstraintValidator
{
    /**
     * @param Order|object $value
     * @param Discounts $constraint
     *
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$value instanceof Order) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Value must be instance of "%s", "%s" given',
                    'Oro\Bundle\OrderBundle\Entity\Order',
                    is_object($value) ? get_class($value) : gettype($value)
                )
            );
        }
        
        if ($value->getTotalDiscounts() && $value->getSubtotal() < $value->getTotalDiscounts()->getValue()) {
            $this->context->buildViolation($constraint->errorMessage)
                ->atPath('totalDiscountsAmount')
                ->addViolation();
        }
    }
}
