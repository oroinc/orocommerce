<?php

declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\ProductKit\ProductPriceCriteria\Builder;

use Oro\Bundle\PricingBundle\Model\ProductPriceCriteriaBuilder\ProductPriceCriteriaBuilderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;

/**
 * Interface for product kit price criteria builders.
 */
interface ProductKitPriceCriteriaBuilderInterface extends ProductPriceCriteriaBuilderInterface
{
    public function addKitItemProduct(
        ProductKitItem $productKitItem,
        Product $product,
        ?ProductUnit $productUnit = null,
        ?float $quantity = null
    ): self;
}
