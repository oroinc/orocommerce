<?php

namespace Oro\Bundle\ShippingBundle\Method;

/**
 * This interface should be implemented by shipping methods that can have a tracking link.
 */
interface ShippingTrackingAwareInterface
{
    public function getTrackingLink(string $number): ?string;
}
