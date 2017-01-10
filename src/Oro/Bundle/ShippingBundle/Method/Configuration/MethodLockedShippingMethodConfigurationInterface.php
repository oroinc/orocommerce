<?php

namespace Oro\Bundle\ShippingBundle\Method\Configuration;

interface MethodLockedShippingMethodConfigurationInterface extends PreConfiguredShippingMethodConfigurationInterface
{
    /**
     * @return bool
     */
    public function isShippingMethodLocked();
}
