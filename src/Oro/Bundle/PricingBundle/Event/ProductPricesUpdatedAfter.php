<?php

namespace Oro\Bundle\PricingBundle\Event;

/**
 * It published immediately after the prices updated.
 */
class ProductPricesUpdatedAfter extends ProductPricesUpdated
{
    public const NAME = 'oro_pricing.product_prices.updated_after';
}
