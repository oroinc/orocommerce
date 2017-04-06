<?php

namespace Oro\Bundle\ShippingBundle\Checker;

use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;

interface ShippingRuleEnabledCheckerInterface
{
    /**
     * @param ShippingMethodsConfigsRule $rule
     *
     * @return bool
     */
    public function canBeEnabled(ShippingMethodsConfigsRule $rule);
}
