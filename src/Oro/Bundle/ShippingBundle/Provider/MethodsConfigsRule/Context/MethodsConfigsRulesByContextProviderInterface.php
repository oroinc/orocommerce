<?php

namespace Oro\Bundle\ShippingBundle\Provider\MethodsConfigsRule\Context;

use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;

interface MethodsConfigsRulesByContextProviderInterface
{
    /**
     * @param ShippingContextInterface $context
     * @return array|ShippingMethodsConfigsRule[]
     */
    public function getShippingMethodsConfigsRules(ShippingContextInterface $context);
}
