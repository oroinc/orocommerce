<?php

namespace Oro\Bundle\CheckoutBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Validates that a checkout has applicable shipping rules to be used.
 */
class HasApplicableShippingRules extends Constraint
{
    public const string CODE = 'has_applicable_shipping_rules';

    public string $message = 'oro.checkout.validator.has_applicable_shipping_rules.message';

    #[\Override]
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
