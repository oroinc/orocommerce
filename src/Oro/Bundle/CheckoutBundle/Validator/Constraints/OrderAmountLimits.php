<?php

namespace Oro\Bundle\CheckoutBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Validates that the shopping list total satisfies the configured minimum and maximum order amount.
 */
class OrderAmountLimits extends Constraint
{
    public const string MINIMUM_NOT_MET_CODE = 'order_minimum_amount_not_met';
    public const string MAXIMUM_NOT_MET_CODE = 'order_maximum_amount_not_met';

    public string $minimumMessage = 'oro.checkout.frontend.checkout.order_limits.minimum_order_amount_flash';
    public string $maximumMessage = 'oro.checkout.frontend.checkout.order_limits.maximum_order_amount_flash';

    #[\Override]
    public function getTargets(): array|string
    {
        return self::CLASS_CONSTRAINT;
    }
}
