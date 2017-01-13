<?php

namespace Oro\Bundle\ShippingBundle\Method\Configuration;

interface OverriddenCostShippingMethodConfigurationInterface extends PreConfiguredShippingMethodConfigurationInterface
{
    /**
     * @return bool
     */
    public function isOverriddenShippingCost();
}
