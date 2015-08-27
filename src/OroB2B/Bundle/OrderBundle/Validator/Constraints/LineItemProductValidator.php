<?php

namespace OroB2B\Bundle\OrderBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;

class LineItemProductValidator extends ConstraintValidator
{
    /**
     * @param OrderLineItem|object $value
     * @param LineItemProduct $constraint
     *
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$value instanceof OrderLineItem) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Value must be instance of "%s", "%s" given',
                    'OroB2B\Bundle\OrderBundle\Entity\OrderLineItem',
                    is_object($value) ? get_class($value) : gettype($value)
                )
            );
        }

        if (!$value->getProduct() && !$value->getFreeFormProduct()) {
            $this->context->buildViolation($constraint->emptyProductMessage)
                 ->atPath('product')
                ->addViolation();
        }

        if (!$value->getPrice()) {
            $this->context->buildViolation($constraint->priceNotFoundMessage)
                ->atPath('product')
                ->addViolation();
        }
    }
}
