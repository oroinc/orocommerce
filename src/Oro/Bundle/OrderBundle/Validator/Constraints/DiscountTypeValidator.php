<?php

namespace Oro\Bundle\OrderBundle\Validator\Constraints;

use Oro\Bundle\OrderBundle\Entity\OrderDiscount;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class DiscountTypeValidator extends ConstraintValidator
{
    private $validTypes = [OrderDiscount::TYPE_AMOUNT, OrderDiscount::TYPE_PERCENT];

    /**
     * @param OrderDiscount|object $value
     * @param DiscountType         $constraint
     *
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (null === $value) {
            return;
        }

        if (!$value instanceof OrderDiscount) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Value must be instance of "%s", "%s" given',
                    OrderDiscount::class,
                    is_object($value) ? get_class($value) : gettype($value)
                )
            );
        }

        if (in_array($value->getType(), $this->validTypes, true)) {
            return;
        }

        $this->context->buildViolation($constraint->errorMessage)
            ->atPath('type')
            ->setParameter('%valid_types%', implode(',', $this->validTypes))
            ->addViolation();
    }
}
