<?php

declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\Model\ProductLineItemPrice\Factory;

use Oro\Bundle\PricingBundle\Model\ProductLineItemPrice\ProductLineItemPrice;
use Oro\Bundle\PricingBundle\Model\ProductPriceInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;

/**
 * Interface for the factories that create a product line item price from a product line item and a product price.
 */
interface ProductLineItemPriceFactoryInterface
{
    public function createForProductLineItem(
        ProductLineItemInterface $lineItem,
        ProductPriceInterface $productPrice
    ): ?ProductLineItemPrice;

    public function isSupported(
        ProductLineItemInterface $lineItem,
        ProductPriceInterface $productPrice
    ): bool;
}
