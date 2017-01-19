<?php

namespace Oro\Bundle\ShippingBundle\Provider;

use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;

interface ShippingMethodsConfigsRulesProviderInterface
{
    /**
     * @param ShippingContextInterface $context
     * @return array|ShippingMethodsConfigsRule[]
     */
    public function getFilteredShippingMethodsConfigsRegardlessDestination(ShippingContextInterface $context);

    /**
     * @param ShippingContextInterface $context
     * @return array|ShippingMethodsConfigsRule[]
     */
    public function getAllFilteredShippingMethodsConfigs(ShippingContextInterface $context);
}

