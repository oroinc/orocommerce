<?php

namespace OroB2B\Bundle\PricingBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class CombinedPriceListsUpdateEvent extends Event
{
    const NAME = 'orob2b_pricing.combined_price_list.update';

    /**
     * @var array
     */
    protected $combinedPriceListIds;

    /**
     * @param array $cplIds
     */
    public function __construct(array $cplIds)
    {
        $this->combinedPriceListIds = $cplIds;
    }

    /**
     * @return array
     */
    public function getCombinedPriceListIds()
    {
        return $this->combinedPriceListIds;
    }
}
