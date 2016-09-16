<?php

namespace Oro\Bundle\ShippingBundle\Method;

class ShippingMethodRegistry implements ShippingMethodProviderInterface
{
    /**
     * @var ShippingMethodProviderInterface[]
     */
    private $providers = [];

    /**
     * @param ShippingMethodProviderInterface $provider
     */
    public function addProvider(ShippingMethodProviderInterface $provider)
    {
        $this->providers[] = $provider;
    }

    /**
     * @param string $name
     * @return ShippingMethodInterface
     */
    public function getShippingMethod($name)
    {
        foreach ($this->providers as $provider) {
            if ($provider->hasShippingMethod($name)) {
                return $provider->getShippingMethod($name);
            }
        }
        return null;
    }

    /**
     * @return ShippingMethodInterface[]
     */
    public function getShippingMethods()
    {
        $result = [];
        foreach ($this->providers as $provider) {
            $result = array_merge($result, $provider->getShippingMethods());
        }
        return $result;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasShippingMethod($name)
    {
        foreach ($this->providers as $provider) {
            if ($provider->hasShippingMethod($name)) {
                return true;
            }
        }
        return false;
    }
}
