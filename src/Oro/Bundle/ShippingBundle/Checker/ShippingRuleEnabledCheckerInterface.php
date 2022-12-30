<?php

namespace Oro\Bundle\ShippingBundle\Checker;

use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;

/**
 * Represents a service to check whether a shipping rule can be enabled or not.
 */
interface ShippingRuleEnabledCheckerInterface
{
    public function canBeEnabled(ShippingMethodsConfigsRule $rule): bool;
}
