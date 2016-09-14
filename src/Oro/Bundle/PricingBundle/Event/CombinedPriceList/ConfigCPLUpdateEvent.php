<?php

namespace Oro\Bundle\PricingBundle\Event\CombinedPriceList;

use Symfony\Component\EventDispatcher\Event;

class ConfigCPLUpdateEvent extends Event
{
    const NAME = 'oro_pricing.config.combined_price_list.update';
}
