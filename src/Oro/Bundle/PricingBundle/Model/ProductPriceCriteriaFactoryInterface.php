<?php

namespace Oro\Bundle\PricingBundle\Model;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;

/**
 * Represents a factory to create the ProductPriceCriteria.
 */
interface ProductPriceCriteriaFactoryInterface
{
    public function build(
        Product $product,
        ProductUnit $productUnit,
        float $quantity,
        ?string $currency = null
    ): ProductPriceCriteria;

    /**
     * @param iterable<ProductLineItemInterface> $productLineItems
     * @param string|null                        $currency
     *
     * @return ProductPriceCriteria[]
     */
    public function createListFromProductLineItems(iterable $productLineItems, ?string $currency = null): array;

    public function createFromProductLineItem(
        ProductLineItemInterface $productLineItem,
        ?string $currency = null
    ): ProductPriceCriteria;
}
