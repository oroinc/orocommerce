<?php

namespace Oro\Bundle\OrderBundle\Validator\Constraints;

use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * Constraint validator checking if either a product or freeFormProduct field is filled in {@see OrderLineItem}.
 */
class LineItemProductValidator extends ConstraintValidator
{
    /**
     * @param OrderLineItem|object $value
     * @param LineItemProduct $constraint
     *
     */
    #[\Override]
    public function validate($value, Constraint $constraint): void
    {
        if (!$value instanceof OrderLineItem) {
            throw new UnexpectedValueException($value, OrderLineItem::class);
        }

        if (!$value->getProduct() && !$value->getFreeFormProduct()) {
            $this->context->buildViolation($constraint->emptyProductMessage)
                ->atPath('product')
                ->addViolation();
        }
    }
}
