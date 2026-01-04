<?php

namespace Oro\Bundle\PricingBundle\Event\CombinedPriceList;

use Symfony\Contracts\EventDispatcher\Event;

class ConfigCPLUpdateEvent extends Event
{
    public const NAME = 'oro_pricing.config.combined_price_list.update';
}
