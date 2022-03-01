<?php

namespace Oro\Bundle\PricingBundle\Async;

/**
 * Pricing Bundle related Message Queue Topics.
 *
 * @deprecated use Topic\*Topic::getName() instead. Will be removed in 5.1.
 */
final class Topics
{
    public const RESOLVE_PRICE_RULES                  = Topic\ResolvePriceRulesTopic::NAME;
    public const RESOLVE_PRICE_LIST_ASSIGNED_PRODUCTS = Topic\ResolvePriceListAssignedProductsTopic::NAME;
    public const REBUILD_COMBINED_PRICE_LISTS         = Topic\RebuildCombinedPriceListsTopic::NAME;
    public const RESOLVE_COMBINED_PRICES              = Topic\ResolveCombinedPriceByPriceListTopic::NAME;
    public const RESOLVE_COMBINED_CURRENCIES          = Topic\ResolveCombinedPriceListCurrenciesTopic::NAME;
}
