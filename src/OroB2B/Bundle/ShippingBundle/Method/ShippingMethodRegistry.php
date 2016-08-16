<?php

namespace OroB2B\Bundle\ShippingBundle\Method;

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
        $this->shippingMethods[$shippingMethod->getName()] = $shippingMethod;
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

        // TODO: return null instead of exception, will be fixed during BB-2812
        throw new \InvalidArgumentException(
            sprintf(
                'Shipping method "%s" is missing. Registered shipping methods are "%s"',
                $name,
                implode(', ', array_keys($this->shippingMethods))
            )
        );
    }

    /**
     * @return ShippingMethodInterface[]
     */
    public function getShippingMethods()
    {
        return $this->shippingMethods;
    }
}
