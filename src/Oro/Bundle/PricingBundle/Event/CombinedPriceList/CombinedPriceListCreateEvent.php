<?php

namespace Oro\Bundle\PricingBundle\Event\CombinedPriceList;

use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Symfony\Component\EventDispatcher\Event;

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

    /**
     * @param CombinedPriceList $combinedPriceList
     */
    public function __construct(CombinedPriceList $combinedPriceList)
    {
        $this->combinedPriceList = $combinedPriceList;
    }

    /**
     * @param array $options
     */
    public function setOptions(array $options)
    {
        $this->options = $options;
    }

    /**
     * @return CombinedPriceList
     */
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
