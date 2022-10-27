<?php

namespace Oro\Bundle\PricingBundle\Event\CombinedPriceList;

use Symfony\Contracts\EventDispatcher\Event;

class CombinedPriceListsUpdateEvent extends Event
{
    const NAME = 'oro_pricing.combined_price_list.update';

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
