<?php

namespace Oro\Bundle\PricingBundle\Event\CombinedPriceList;

use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Provides event arguments for the actualize schedule event of CombinedPriceList.
 */
class CombinedPriceListActualizeScheduleEvent extends Event
{
    public const NAME = 'oro_pricing.combined_price_list.actualize_schedule';

    private CombinedPriceList $combinedPriceList;

    public function __construct(CombinedPriceList $combinedPriceList)
    {
        $this->combinedPriceList = $combinedPriceList;
    }

    public function getCombinedPriceList(): CombinedPriceList
    {
        return $this->combinedPriceList;
    }
}
