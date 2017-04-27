<?php

namespace Oro\Bundle\ShippingBundle\Provider;

interface ShippingMethodIconProviderInterface
{
    /**
     * @param string $identifier
     *
     * @return string|null
     */
    public function getIcon($identifier);
}
