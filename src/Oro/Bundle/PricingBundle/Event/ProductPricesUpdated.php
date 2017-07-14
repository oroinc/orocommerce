<?php

namespace Oro\Bundle\PricingBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class ProductPricesUpdated extends Event
{
    const NAME = 'oro_pricing.product_prices.updated';
}
