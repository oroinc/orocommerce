<?php

namespace Oro\Bundle\PricingBundle\Async;

class Topics
{
    const CALCULATE_RULE = 'oro_pricing.price_rule.calculate';
    const REBUILD_PRICE_LISTS = 'oro_pricing.price_lists.rebuild';
    const PRICE_LIST_CHANGE = 'oro_pricing.price_lists.change';
}
