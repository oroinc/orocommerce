<?php

namespace Oro\Bundle\CheckoutBundle\Validator\Constraints;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates checkout shipping address presence.
 */
class NotEmptyShippingAddressValidator extends ConstraintValidator
{
    #[\Override]
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof NotEmptyShippingAddress) {
            throw new UnexpectedTypeException($constraint, NotEmptyShippingAddress::class);
        }

        if (null !== $value && !$value instanceof OrderAddress) {
            throw new UnexpectedTypeException($value, OrderAddress::class);
        }

        $checkout = $this->context->getObject();
        if (null === $checkout) {
            return;
        }
        if (!$checkout instanceof Checkout) {
            throw new UnexpectedTypeException($value, Checkout::class);
        }

        if ($checkout->isShipToBillingAddress()) {
            return;
        }

        if (null === $value) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}
