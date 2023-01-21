<?php

namespace Oro\Bundle\CheckoutBundle\Provider\MultiShipping;

use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodProviderInterface;

/**
 * Providers available shipping methods for a checkout or main orders created during multiple shipping flow.
 */
class DefaultMultipleShippingMethodProvider
{
    private ShippingMethodProviderInterface $multiShippingMethodProvider;

    public function __construct(ShippingMethodProviderInterface $multiShippingMethodProvider)
    {
        $this->multiShippingMethodProvider = $multiShippingMethodProvider;
    }

    /**
     * Gets the first configured Multi Shipping method.
     */
    public function getShippingMethod(): ShippingMethodInterface
    {
        $methods = $this->multiShippingMethodProvider->getShippingMethods();
        if (!$methods) {
            throw new \LogicException('There are no enabled multi shipping methods');
        }

        return reset($methods);
    }

    /**
     * @return string[]
     */
    public function getShippingMethods(): array
    {
        $methods = $this->multiShippingMethodProvider->getShippingMethods();
        if (!$methods) {
            throw new \LogicException('There are no enabled multi shipping methods');
        }

        return array_keys($methods);
    }

    public function hasShippingMethods(): bool
    {
        $methods = $this->multiShippingMethodProvider->getShippingMethods();

        return !empty($methods);
    }
}
