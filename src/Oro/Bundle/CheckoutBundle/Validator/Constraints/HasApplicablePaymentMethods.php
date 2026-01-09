<?php

namespace Oro\Bundle\CheckoutBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Validates that a checkout has applicable payment methods to be used.
 */
class HasApplicablePaymentMethods extends Constraint
{
    public const string CODE = 'has_applicable_payment_methods';

    public string $message = 'oro.checkout.validator.has_applicable_payment_methods.message';

    #[\Override]
    public function getTargets(): array|string
    {
        return self::CLASS_CONSTRAINT;
    }
}
