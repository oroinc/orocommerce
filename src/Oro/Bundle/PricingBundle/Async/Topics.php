<?php

namespace Oro\Bundle\PricingBundle\Async;

/**
 * Pricing Bundle related Message Queue Topics
 */
final class Topics
{
    public const RESOLVE_PRICE_RULES                  = 'oro_pricing.price_rule.build';
    public const RESOLVE_PRICE_LIST_ASSIGNED_PRODUCTS = 'oro_pricing.price_lists.resolve_assigned_products';
    public const REBUILD_COMBINED_PRICE_LISTS         = 'oro_pricing.price_lists.cpl.rebuild';
    public const RESOLVE_COMBINED_PRICES              = 'oro_pricing.price_lists.cpl.resolve_prices';
    public const RESOLVE_COMBINED_CURRENCIES          = 'oro_pricing.price_lists.cpl.resolve_currencies';
}
