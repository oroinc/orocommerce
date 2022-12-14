<?php

namespace Oro\Bundle\CheckoutBundle\Provider\MultiShipping;

use Oro\Bundle\ShippingBundle\Method\MultiShippingMethodProvider;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;

/**
 * Provider for available shipping methods for checkout or main orders created during multiple shipping flow.
 */
class DefaultMultipleShippingMethodProvider
{
    private MultiShippingMethodProvider $shippingProvider;
    private ?array $shippingMethods = null;

    public function __construct(MultiShippingMethodProvider $shippingProvider)
    {
        $this->shippingProvider = $shippingProvider;
    }

    /**
     * Get first configured multishipping method.
     *
     * @return ShippingMethodInterface
     */
    public function getShippingMethod(): ShippingMethodInterface
    {
        $methods = $this->getCachedShippingMethods();

        if (!$methods) {
            throw new \LogicException('There are no enabled multi shipping methods');
        }

        return reset($methods);
    }

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
