<?php

namespace Oro\Bundle\CheckoutBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Validates checkout shipping address presence.
 */
class NotEmptyShippingAddress extends Constraint
{
    public const string CODE = 'empty_checkout_shipping_address';

    public string $message = 'oro.checkout.workflow.condition.invalid_shipping_address.messa';

    #[\Override]
    public function getTargets()
    {
        return self::PROPERTY_CONSTRAINT;
    }
}
