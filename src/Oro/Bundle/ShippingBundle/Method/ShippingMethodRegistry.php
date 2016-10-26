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
     * @param string $identifier
     * @return null|ShippingMethodInterface
     */
    public function getShippingMethod($identifier)
    {
        foreach ($this->providers as $provider) {
            if ($provider->hasShippingMethod($identifier)) {
                return $provider->getShippingMethod($identifier);
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

    /**
     * @return ShippingMethodInterface[]
     */
    public function getTrackingAwareShippingMethods()
    {
        $result = [];
        foreach ($this->providers as $provider) {
            foreach ($provider->getShippingMethods() as $shippingMethod) {
                if ($shippingMethod instanceof ShippingTrackingAwareInterface) {
                    $result[$shippingMethod->getIdentifier()] = $shippingMethod;
                }
            }
        }
        return $result;
    }
}
