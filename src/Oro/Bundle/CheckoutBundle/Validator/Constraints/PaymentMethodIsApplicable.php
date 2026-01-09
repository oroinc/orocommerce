<?php

namespace Oro\Bundle\CheckoutBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Validates that a selected checkout payment method is applicable.
 */
class PaymentMethodIsApplicable extends Constraint
{
    public const string CODE = 'payment_method_is_applicable';

    public string $message = 'oro.checkout.validator.payment_method_is_applicable.message';

    #[\Override]
    public function getTargets(): array|string
    {
        return self::CLASS_CONSTRAINT;
    }
}
