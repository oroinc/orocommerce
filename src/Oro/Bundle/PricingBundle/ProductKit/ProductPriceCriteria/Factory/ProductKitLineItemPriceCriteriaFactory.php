<?php

declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\ProductKit\ProductPriceCriteria\Factory;

use Oro\Bundle\PricingBundle\Model\ProductLineItemPriceCriteriaFactory\ProductLineItemPriceCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\ProductKit\ProductPriceCriteria\Builder\ProductKitPriceCriteriaBuilderInterface;
use Oro\Bundle\PricingBundle\ProductKit\ProductPriceCriteria\ProductKitPriceCriteria;
use Oro\Bundle\ProductBundle\Model\ProductKitItemLineItemsAwareInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;

/**
 * Creates a product kit line item price criteria for the specified product line item.
 */
class ProductKitLineItemPriceCriteriaFactory implements ProductLineItemPriceCriteriaFactoryInterface
{
    private ProductKitPriceCriteriaBuilderInterface $productKitPriceCriteriaBuilder;

    public function __construct(ProductKitPriceCriteriaBuilderInterface $productKitPriceCriteriaBuilder)
    {
        $this->productKitPriceCriteriaBuilder = $productKitPriceCriteriaBuilder;
    }

    public function createFromProductLineItem(
        ProductLineItemInterface|ProductKitItemLineItemsAwareInterface $lineItem,
        ?string $currency
    ): ?ProductKitPriceCriteria {
        if (!$this->isSupported($lineItem, $currency)) {
            return null;
        }

        $productKitPriceCriteriaBuilder = $this->productKitPriceCriteriaBuilder
            ->setProduct($lineItem->getProduct())
            ->setProductUnit($lineItem->getProductUnit())
            ->setQuantity((float)$lineItem->getQuantity())
            ->setCurrency($currency);

        foreach ($lineItem->getKitItemLineItems() as $kitItemLineItem) {
            if ($kitItemLineItem->getProduct()) {
                $productKitPriceCriteriaBuilder->addKitItemProduct(
                    $kitItemLineItem->getKitItem(),
                    $kitItemLineItem->getProduct(),
                    $kitItemLineItem->getProductUnit(),
                    (float)$kitItemLineItem->getQuantity()
                );
            }
        }

        return $productKitPriceCriteriaBuilder->create();
    }

    public function isSupported(ProductLineItemInterface $lineItem, ?string $currency): bool
    {
        return $lineItem instanceof ProductKitItemLineItemsAwareInterface
            && $lineItem->getProduct()?->isKit()
            && $lineItem->getProductUnit() !== null;
    }
}
