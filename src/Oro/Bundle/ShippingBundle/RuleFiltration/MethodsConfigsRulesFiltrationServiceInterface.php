<?php

namespace Oro\Bundle\ShippingBundle\RuleFiltration;

use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;

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
