<?php

namespace OroB2B\Bundle\PricingBundle\Event\CombinedPriceList;

use Symfony\Component\EventDispatcher\Event;

class ConfigCPLUpdateEvent extends Event
{
    const NAME = 'orob2b_pricing.config.combined_price_list.update';
}
