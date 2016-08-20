<?php

namespace Oro\Bundle\PricingBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class PriceRuleChange extends Event
{
    const NAME = 'orob2b_pricing.price_rule.change';
}
