<?php

namespace Oro\Bundle\CheckoutBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Validates that a selected checkout shipping method is valid.
 */
class ShippingMethodIsValid extends Constraint
{
    public const string CODE = 'shipping_method_is_valid';

    public string $shippingMethodMessage = 'oro.checkout.validator.shipping_method_is_invalid.message';
    public string $shippingMethodTypeMessage = 'oro.checkout.validator.shipping_method_type_is_invalid.message';

    #[\Override]
    public function getTargets(): array|string
    {
        return self::CLASS_CONSTRAINT;
    }
}
