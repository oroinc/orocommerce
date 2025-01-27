<?php

namespace Oro\Bundle\CheckoutBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Validates checkout address.
 */
class ValidCheckoutAddress extends Constraint
{
    public const string CODE = 'invalid_checkout_address';

    public string $message = 'oro.checkout.validator.invalid_checkout_addresses.message';

    #[\Override]
    public function getTargets()
    {
        return [self::CLASS_CONSTRAINT, self::PROPERTY_CONSTRAINT];
    }
}
