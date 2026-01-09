<?php

declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\ProductKit\ProductPriceCriteria\Factory;

use Oro\Bundle\PricingBundle\ProductKit\ProductPriceCriteria\Builder\ProductKitPriceCriteriaBuilderInterface;
use Oro\Bundle\ProductBundle\Model\ProductKitItemLineItemInterface;
use Oro\Bundle\ProductBundle\Model\ProductKitItemLineItemsAwareInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;

/**
 * Creates a product kit line item price criteria builder for the specified product line item.
 */
class ProductKitLineItemPriceCriteriaBuilderFactory
{
    private ProductKitPriceCriteriaBuilderInterface $productKitPriceCriteriaBuilder;

    public function __construct(ProductKitPriceCriteriaBuilderInterface $productKitPriceCriteriaBuilder)
    {
        $this->productKitPriceCriteriaBuilder = $productKitPriceCriteriaBuilder;
    }

    public function createFromProductLineItem(
        ProductLineItemInterface|ProductKitItemLineItemsAwareInterface $lineItem,
        ?string $currency
    ): ?ProductKitPriceCriteriaBuilderInterface {
        if (!$this->isLineItemSupported($lineItem)) {
            return null;
        }

        /** @var ProductKitPriceCriteriaBuilderInterface $productKitPriceCriteriaBuilder */
        $productKitPriceCriteriaBuilder = (clone $this->productKitPriceCriteriaBuilder)
            ->setProduct($lineItem->getProduct())
            ->setProductUnit($lineItem->getProductUnit())
            ->setQuantity((float)$lineItem->getQuantity())
            ->setCurrency($currency);

        if ($lineItem instanceof ProductKitItemLineItemsAwareInterface) {
            foreach ($lineItem->getKitItemLineItems() as $kitItemLineItem) {
                if (
                    !$this->isKitItemLineItemSupported($kitItemLineItem)
                    || !$this->isProductAvailableInKitItem($kitItemLineItem)
                ) {
                    continue;
                }

                $productKitPriceCriteriaBuilder->addKitItemProduct(
                    $kitItemLineItem->getKitItem(),
                    $kitItemLineItem->getProduct(),
                    $kitItemLineItem->getProductUnit(),
                    (float)$kitItemLineItem->getQuantity()
                );
            }
        }

        return $productKitPriceCriteriaBuilder;
    }

    private function isLineItemSupported(ProductLineItemInterface $lineItem): bool
    {
        return $lineItem->getProduct() !== null
            && $lineItem->getProductUnit() !== null
            && $lineItem->getQuantity() !== null
            && $lineItem->getQuantity() >= 0.0;
    }

    private function isKitItemLineItemSupported(ProductKitItemLineItemInterface $kitItemLineItem): bool
    {
        return $kitItemLineItem->getKitItem() !== null
            && $this->isLineItemSupported($kitItemLineItem);
    }

    private function isProductAvailableInKitItem(ProductKitItemLineItemInterface $kitItemLineItem): bool
    {
        $kitItem = $kitItemLineItem->getKitItem();
        $product = $kitItemLineItem->getProduct();

        if (null === $kitItem || null === $product) {
            return false;
        }

        foreach ($kitItem->getKitItemProducts() as $kitItemProduct) {
            if ($product->getId() === $kitItemProduct->getProduct()?->getId()) {
                return true;
            }
        }

        return false;
    }
}
