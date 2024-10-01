<?php

declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\ProductKit\ProductPriceCriteria\Builder;

use Oro\Bundle\PricingBundle\Model\ProductPriceCriteriaBuilder\AbstractProductPriceCriteriaBuilder;
use Oro\Bundle\PricingBundle\ProductKit\ProductPriceCriteria\ProductKitItemPriceCriteria;
use Oro\Bundle\PricingBundle\ProductKit\ProductPriceCriteria\ProductKitPriceCriteria;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;

/**
 * Creates {@see ProductKitPriceCriteria}.
 *
 * @method ProductKitPriceCriteria create()
 */
class ProductKitPriceCriteriaBuilder extends AbstractProductPriceCriteriaBuilder implements
    ProductKitPriceCriteriaBuilderInterface
{
    private array $kitItemsProducts = [];

    #[\Override]
    public function addKitItemProduct(
        ProductKitItem $productKitItem,
        Product $product,
        ?ProductUnit $productUnit = null,
        ?float $quantity = null
    ): self {
        $this->kitItemsProducts[$productKitItem->getId()] = [
            $productKitItem,
            $product,
            $productUnit ?? $productKitItem->getProductUnit(),
            $quantity ?? $productKitItem->getMinimumQuantity(),
        ];

        return $this;
    }

    #[\Override]
    protected function doCreate(): ProductKitPriceCriteria
    {
        $currency = $this->getCurrencyWithFallback();
        $productKitPriceCriteria = new ProductKitPriceCriteria(
            $this->product,
            $this->productUnit,
            $this->quantity,
            $currency
        );

        foreach ($this->kitItemsProducts as [$productKitItem, $product, $productUnit, $quantity]) {
            $productKitPriceCriteria->addKitItemProductPriceCriteria(
                new ProductKitItemPriceCriteria($productKitItem, $product, $productUnit, $quantity, $currency)
            );
        }

        return $productKitPriceCriteria;
    }

    #[\Override]
    public function isSupported(Product $product): bool
    {
        return $product->isKit();
    }

    #[\Override]
    public function reset(): void
    {
        parent::reset();

        $this->kitItemsProducts = [];
    }
}
