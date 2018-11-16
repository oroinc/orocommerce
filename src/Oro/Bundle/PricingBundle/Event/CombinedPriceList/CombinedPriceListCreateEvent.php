<?php

namespace Oro\Bundle\PricingBundle\Event\CombinedPriceList;

use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Symfony\Component\EventDispatcher\Event;

class CombinedPriceListCreateEvent extends Event
{
    const NAME = 'oro_pricing.combined_price_list.create';

    /**
     * @var CombinedPriceList
     */
    protected $combinedPriceList;

    /**
     * @param CombinedPriceList $combinedPriceList
     */
    public function __construct(CombinedPriceList $combinedPriceList)
    {
        $this->combinedPriceList = $combinedPriceList;
    }

    /**
     * @return CombinedPriceList
     */
    public function getCombinedPriceList(): CombinedPriceList
    {
        return $this->combinedPriceList;
    }
}
