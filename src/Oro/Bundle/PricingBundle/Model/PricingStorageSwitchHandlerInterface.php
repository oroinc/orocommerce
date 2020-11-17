<?php

namespace Oro\Bundle\PricingBundle\Model;

/**
 * Handles pricing storage switches.
 */
interface PricingStorageSwitchHandlerInterface
{
    /**
     * Handle switch to flat pricing storage.
     * Perform required storage reorganizations.
     */
    public function moveAssociationsForFlatPricingStorage(): void;

    /**
     * Handle switch to combined pricing storage.
     * Perform required storage reorganizations.
     */
    public function moveAssociationsForCombinedPricingStorage(): void;
}
