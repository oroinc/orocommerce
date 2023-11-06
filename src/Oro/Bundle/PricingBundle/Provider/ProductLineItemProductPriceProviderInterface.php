<?php

declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\Provider;

use Oro\Bundle\PricingBundle\Model\DTO\ProductPriceCollectionDTO;
use Oro\Bundle\PricingBundle\Model\ProductPriceInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;

/**
 * Provides available product prices for the specified product line item.
 */
interface ProductLineItemProductPriceProviderInterface
{
    /**
     * @param ProductLineItemInterface $productLineItem
     * @param ProductPriceCollectionDTO $productPriceCollection
     * @param string $currency
     *
     * @return array<ProductPriceInterface>
     */
    public function getProductLineItemProductPrices(
        ProductLineItemInterface $productLineItem,
        ProductPriceCollectionDTO $productPriceCollection,
        string $currency
    ): array;
}
