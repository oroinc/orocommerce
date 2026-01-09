<?php

namespace Oro\Bundle\PricingBundle\Event\CombinedPriceList;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched when combined price lists are updated.
 *
 * This event carries the IDs of combined price lists that have been updated,
 * allowing listeners to react to changes in combined pricing data.
 */
class CombinedPriceListsUpdateEvent extends Event
{
    public const NAME = 'oro_pricing.combined_price_list.update';

    /**
     * @var array
     */
    protected $combinedPriceListIds;

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
