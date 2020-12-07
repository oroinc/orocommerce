<?php

namespace Oro\Bundle\FedexShippingBundle\Model;

/**
 * Represents fedex package dimensional settings
 */
interface FedexPackageDimensionalSettingsInterface
{
    /**
     * @return bool
     */
    public function isDimensionsIgnored(): bool;
}
