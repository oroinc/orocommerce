<?php

namespace Oro\Bundle\PricingBundle\Async;

class Topics
{
    const RESOLVE_PRICE_RULES = 'oro_pricing.price_rule.build';
    const RESOLVE_PRICE_LIST_ASSIGNED_PRODUCTS = 'oro_pricing.price_lists.resolve_assigned_products';
    const REBUILD_COMBINED_PRICE_LISTS = 'oro_pricing.price_lists.cpl.rebuild';
    const RESOLVE_COMBINED_PRICES = 'oro_pricing.price_lists.cpl.resolve_prices';
}
