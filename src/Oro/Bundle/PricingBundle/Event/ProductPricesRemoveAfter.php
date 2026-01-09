<?php

namespace Oro\Bundle\PricingBundle\Event;

/**
 * Dispatched after product prices are removed.
 *
 * This event allows listeners to react to the completion of product price removal operations.
 */
class ProductPricesRemoveAfter extends AbstractProductPricesRemoveEvent
{
    public const NAME = 'oro_pricing.product_price.remove_after';
}
