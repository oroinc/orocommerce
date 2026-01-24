<?php

namespace Oro\Bundle\PricingBundle\SubtotalProcessor\Model;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * Defines the contract for entities that contain unpriced line items.
 *
 * Entities implementing this interface provide access to line items that have not yet
 * been assigned prices, allowing subtotal processors to handle pricing calculations.
 */
interface LineItemsNotPricedAwareInterface
{
    /**
     * @return ArrayCollection
     */
    public function getLineItems();
}
