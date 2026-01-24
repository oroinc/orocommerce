<?php

namespace Oro\Bundle\OrderBundle\Model;

use Oro\Bundle\CurrencyBundle\Entity\Price;

/**
 * Defines the contract for entities that support shipping cost information.
 *
 * Implementations of this interface represent entities that have associated shipping costs,
 * providing access to the shipping cost as a {@see Price} object which includes both value and currency information.
 */
interface ShippingAwareInterface
{
    /**
     * Get shipping estimate
     *
     * @return Price|null
     */
    public function getShippingCost();
}
