<?php

namespace Oro\Bundle\ShippingBundle\Method;

class ShippingMethodRegistry
{
    /**
     * @var ShippingMethodInterface[]
     */
    protected $shippingMethods = [];

    /**
     * @param ShippingMethodInterface $shippingMethod
     */
    public function addShippingMethod(ShippingMethodInterface $shippingMethod)
    {
        $this->shippingMethods[$shippingMethod->getIdentifier()] = $shippingMethod;
    }

    /**
     * @param string $name
     * @return ShippingMethodInterface
     */
    public function getShippingMethod($name)
    {
        $name = (string)$name;

        if (array_key_exists($name, $this->shippingMethods)) {
            return $this->shippingMethods[$name];
        }

        return null;
    }

    /**
     * @return ShippingMethodInterface[]
     */
    public function getShippingMethods()
    {
        return $this->shippingMethods;
    }
}
