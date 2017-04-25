<?php

namespace Oro\Bundle\ShippingBundle\Provider;

interface ShippingMethodIconProviderInterface
{
    /**
     * @param string $identifier
     *
     * @return string
     */
    public function getIcon($identifier);
}
