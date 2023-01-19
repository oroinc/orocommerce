<?php

namespace Oro\Bundle\CheckoutBundle\Provider\MultiShipping;

use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodProviderInterface;

/**
 * Providers available shipping methods for a checkout or main orders created during multiple shipping flow.
 */
class DefaultMultipleShippingMethodProvider
{
    private ShippingMethodProviderInterface $shippingProvider;
    private ?array $shippingMethods = null;

    public function __construct(ShippingMethodProviderInterface $shippingProvider)
    {
        $this->shippingProvider = $shippingProvider;
    }

    /**
     * Gets the first configured Multi Shipping method.
     */
    public function getShippingMethod(): ShippingMethodInterface
    {
        $methods = $this->getCachedShippingMethods();
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
        $methods = $this->getCachedShippingMethods();
        if (!$methods) {
            throw new \LogicException('There are no enabled multi shipping methods');
        }

        return array_keys($methods);
    }

    public function hasShippingMethods(): bool
    {
        $methods = $this->getCachedShippingMethods();

        return !empty($methods);
    }

    private function getCachedShippingMethods(): array
    {
        if (null === $this->shippingMethods) {
            $this->shippingMethods = $this->shippingProvider->getShippingMethods();
        }

        return $this->shippingMethods;
    }
}
