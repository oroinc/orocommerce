<?php

namespace Oro\Bundle\PricingBundle\Event\CombinedPriceList;

use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Provides event arguments for the create event of CombinedPriceList.
 */
class CombinedPriceListCreateEvent extends Event
{
    public const NAME = 'oro_pricing.combined_price_list.create';

    /** @var CombinedPriceList */
    private $combinedPriceList;

    /** @var array */
    private $options = [];

    public function __construct(CombinedPriceList $combinedPriceList, array $options = [])
    {
        $this->combinedPriceList = $combinedPriceList;
        $this->options = $options;
    }

    public function getCombinedPriceList(): CombinedPriceList
    {
        return $this->combinedPriceList;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }
}
