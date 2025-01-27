<?php

namespace Oro\Bundle\CheckoutBundle\Validator\Constraints;

use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates checkout address.
 */
class ValidCheckoutAddressValidator extends ConstraintValidator
{
    #[\Override]
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidCheckoutAddress) {
            throw new UnexpectedTypeException($constraint, ValidCheckoutAddress::class);
        }

        if (null === $value) {
            return;
        }

        if (!$value instanceof OrderAddress) {
            throw new UnexpectedTypeException($value, OrderAddress::class);
        }

        $violationsList = $this->context->getValidator()->validate($value);
        if (\count($violationsList) > 0) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}
