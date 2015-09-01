<?php

namespace OroB2B\Bundle\OrderBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use OroB2B\Bundle\OrderBundle\Entity\Order;

class OrderLineItemCountValidator extends ConstraintValidator
{
    /**
     * @param Order|object $value
     * @param OrderLineItemCount $constraint
     *
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$value instanceof Order) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Value must be instance of "%s", "%s" given',
                    'OroB2B\Bundle\OrderBundle\Entity\Order',
                    is_object($value) ? get_class($value) : gettype($value)
                )
            );
        }

        if (!count($value->getLineItems())) {
            $this->context->buildViolation($constraint->minLineItemCountMessage)
                ->atPath('lineItems')
                ->addViolation();
        }
    }
}
