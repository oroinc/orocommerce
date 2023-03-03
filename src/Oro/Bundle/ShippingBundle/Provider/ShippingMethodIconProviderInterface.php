<?php

namespace Oro\Bundle\ShippingBundle\Provider;

/**
 * Represents a service to get an icon for a shipping method.
 */
interface ShippingMethodIconProviderInterface
{
    public function getIcon(string $identifier): ?string;
}
