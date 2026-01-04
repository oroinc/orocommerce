<?php

namespace Oro\Bundle\PricingBundle\Event;

class ProductPricesRemoveBefore extends AbstractProductPricesRemoveEvent
{
    public const NAME = 'oro_pricing.product_price.remove_before';
}
