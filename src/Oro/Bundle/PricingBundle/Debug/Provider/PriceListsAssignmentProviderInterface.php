<?php

namespace Oro\Bundle\PricingBundle\Debug\Provider;

/**
 * Interface for price list assignments providers.
 */
interface PriceListsAssignmentProviderInterface
{
    public function getPriceListAssignments(): ?array;
}
