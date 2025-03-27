<?php

namespace Oro\Bundle\CheckoutBundle\Api\Validator;

use Oro\Bundle\CheckoutBundle\Api\GuestCheckoutChecker;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates that a visitor has an access to a customer user
 * when the checkout feature is enabled for visitors.
 */
class GuestCheckoutCustomerUserValidator extends ConstraintValidator
{
    public function __construct(
        private readonly GuestCheckoutChecker $guestCheckoutChecker
    ) {
    }

    #[\Override]
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof GuestCheckoutCustomerUser) {
            throw new UnexpectedTypeException($constraint, GuestCheckoutCustomerUser::class);
        }

        if (null === $value) {
            return;
        }

        if (!$value instanceof CustomerUser) {
            throw new UnexpectedTypeException($value, CustomerUser::class);
        }

        if (null === $value->getId()) {
            return;
        }

        $guestCustomerUser = $this->guestCheckoutChecker->getVisitor()->getCustomerUser();
        if (null === $guestCustomerUser || $guestCustomerUser->getId() !== $value->getId()) {
            $this->context->addViolation(
                $constraint->message,
                ['{{ permission }}' => BasicPermission::VIEW]
            );
        }
    }
}
