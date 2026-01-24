<?php

namespace Oro\Bundle\PricingBundle\Event\CombinedPriceList;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched when the system configuration for combined price lists is updated.
 *
 * This event is triggered when the default combined price list configuration changes,
 * allowing listeners to react to configuration updates.
 */
class ConfigCPLUpdateEvent extends Event
{
    const NAME = 'oro_pricing.config.combined_price_list.update';
}
