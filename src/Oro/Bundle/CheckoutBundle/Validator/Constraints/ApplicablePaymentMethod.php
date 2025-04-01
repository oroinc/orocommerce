<?php

namespace Oro\Bundle\CheckoutBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * This constraint is used to check whether the selected payment method is applicable.
 */
class ApplicablePaymentMethod extends Constraint
{
    public const string CODE = 'applicable_payment_method';

    public string $message = 'oro.checkout.validator.applicable_payment_method.message';

    #[\Override]
    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
