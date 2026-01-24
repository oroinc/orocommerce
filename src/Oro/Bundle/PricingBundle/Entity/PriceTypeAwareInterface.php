<?php

namespace Oro\Bundle\PricingBundle\Entity;

/**
 * Defines the contract for entities that have a price type (unit or bundled).
 *
 * Entities implementing this interface can be classified as having unit-level pricing
 * or bundled pricing, affecting how their prices are calculated and applied.
 */
interface PriceTypeAwareInterface
{
    const PRICE_TYPE_UNIT = 10;
    const PRICE_TYPE_BUNDLED = 20;

    /**
     * @return int
     */
    public function getPriceType();
}
