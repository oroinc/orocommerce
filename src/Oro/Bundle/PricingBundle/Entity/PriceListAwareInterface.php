<?php

namespace Oro\Bundle\PricingBundle\Entity;

/**
 * Defines the contract for entities that are aware of and associated with a price list.
 *
 * Entities implementing this interface can be linked to a specific price list and maintain
 * a sort order for determining priority when multiple price lists are applicable.
 */
interface PriceListAwareInterface
{
    /**
     * @return PriceList
     */
    public function getPriceList();

    /**
     * @param PriceList $priceList
     * @return mixed
     */
    public function setPriceList(PriceList $priceList);

    /**
     * @return int
     */
    public function getSortOrder();
}
