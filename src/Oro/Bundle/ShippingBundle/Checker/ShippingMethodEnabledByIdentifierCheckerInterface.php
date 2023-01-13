<?php

namespace Oro\Bundle\ShippingBundle\Checker;

/**
 * Represents a service to check whether a shipping method with a specific identifier is enabled or not.
 */
interface ShippingMethodEnabledByIdentifierCheckerInterface
{
    public function isEnabled(string $identifier): bool;
}
