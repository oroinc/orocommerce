<?php

namespace Oro\Bundle\ShippingBundle\Method;

/**
 * Represents a service to provide shipping methods.
 */
interface ShippingMethodProviderInterface
{
    /**
     * @return ShippingMethodInterface[] [shipping method name => shipping method, ...]
     */
    public function getShippingMethods(): array;

    public function getShippingMethod(string $name): ?ShippingMethodInterface;

    public function hasShippingMethod(string $name): bool;
}
