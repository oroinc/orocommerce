<?php

namespace Oro\Bundle\ShippingBundle\Provider\MethodsConfigsRule\Context;

use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;

/**
 * Represents a service to get shipping method config rules.
 */
interface MethodsConfigsRulesByContextProviderInterface
{
    /**
     * @param ShippingContextInterface $context
     *
     * @return ShippingMethodsConfigsRule[]
     */
    public function getShippingMethodsConfigsRules(ShippingContextInterface $context): array;
}
