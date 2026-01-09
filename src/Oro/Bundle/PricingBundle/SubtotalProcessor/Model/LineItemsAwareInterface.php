<?php

namespace Oro\Bundle\PricingBundle\SubtotalProcessor\Model;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * Defines the contract for entities that contain line items for subtotal processing.
 */
interface LineItemsAwareInterface
{
    /**
     * @return ArrayCollection
     */
    public function getLineItems();
}
