<?php

namespace Oro\Bundle\PricingBundle\Model;

use Oro\Bundle\PricingBundle\Model\ProductPriceCriteriaBuilder\ProductPriceCriteriaBuilderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;

/**
 * Represents a factory to create the {@see ProductPriceCriteria}.
 */
interface ProductPriceCriteriaFactoryInterface
{
    public function buildFromProduct(Product $product): ProductPriceCriteriaBuilderInterface;

    public function create(
        Product $product,
        ProductUnit $productUnit,
        float $quantity,
        ?string $currency = null
    ): ?ProductPriceCriteria;

    /**
     * @param iterable<ProductLineItemInterface> $productLineItems
     * @param string|null $currency
     *
     * @return array<int|string,ProductPriceCriteria> Products price criteria, each element associated
     *  with the key of the corresponding line item from $lineItems.
     */
    public function createListFromProductLineItems(iterable $productLineItems, ?string $currency = null): array;

    public function createFromProductLineItem(
        ProductLineItemInterface $productLineItem,
        ?string $currency = null
    ): ?ProductPriceCriteria;
}
