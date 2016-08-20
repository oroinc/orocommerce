<?php

namespace Oro\Bundle\PricingBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class ProductPriceChange extends Event
{
    const NAME = 'orob2b_pricing.product_price.change';
}
