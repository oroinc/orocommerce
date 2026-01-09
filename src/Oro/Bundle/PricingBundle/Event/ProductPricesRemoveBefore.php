<?php

namespace Oro\Bundle\PricingBundle\Event;

/**
 * Dispatched before product prices are removed.
 *
 * This event allows listeners to prepare for or prevent product price removal operations.
 */
class ProductPricesRemoveBefore extends AbstractProductPricesRemoveEvent
{
    public const NAME = 'oro_pricing.product_price.remove_before';
}
