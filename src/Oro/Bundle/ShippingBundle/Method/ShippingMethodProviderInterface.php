<?php

namespace Oro\Bundle\ShippingBundle\Method;

interface ShippingMethodProviderInterface
{
    /**
     * @return ShippingMethodInterface[]
     */
    public function getShippingMethods();

    /**
     * @param string $name
     * @return ShippingMethodInterface
     */
    public function getShippingMethod($name);

    /**
     * @param string $name
     * @return bool
     */
    public function hasShippingMethod($name);
}
