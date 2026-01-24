<?php

namespace Oro\Bundle\ShippingBundle\RuleFiltration;

use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;

/**
 * Defines the contract for services that filter shipping method configuration rules.
 *
 * Implementations of this interface evaluate shipping rules against the shipping context,
 * returning only the rules that match the current conditions and should be applied
 * to determine available shipping methods and their configurations.
 */
interface MethodsConfigsRulesFiltrationServiceInterface
{
    /**
     * @param ShippingMethodsConfigsRule[] $shippingMethodsConfigsRules
     * @param ShippingContextInterface     $context
     *
     * @return ShippingMethodsConfigsRule[]
     */
    public function getFilteredShippingMethodsConfigsRules(
        array $shippingMethodsConfigsRules,
        ShippingContextInterface $context
    );
}
