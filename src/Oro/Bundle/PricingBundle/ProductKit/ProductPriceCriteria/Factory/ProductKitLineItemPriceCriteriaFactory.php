<?php

declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\ProductKit\ProductPriceCriteria\Factory;

use Oro\Bundle\PricingBundle\Model\ProductLineItemPriceCriteriaFactory\ProductLineItemPriceCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\ProductKit\ProductPriceCriteria\ProductKitPriceCriteria;
use Oro\Bundle\ProductBundle\Model\ProductKitItemLineItemsAwareInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;

/**
 * Creates a product kit line item price criteria for the specified product line item.
 */
class ProductKitLineItemPriceCriteriaFactory implements ProductLineItemPriceCriteriaFactoryInterface
{
    private ProductKitLineItemPriceCriteriaBuilderFactory $productKitLineItemPriceCriteriaBuilderFactory;

    public function __construct(
        ProductKitLineItemPriceCriteriaBuilderFactory $productKitLineItemPriceCriteriaBuilderFactory
    ) {
        $this->productKitLineItemPriceCriteriaBuilderFactory = $productKitLineItemPriceCriteriaBuilderFactory;
    }

    #[\Override]
    public function createFromProductLineItem(
        ProductLineItemInterface|ProductKitItemLineItemsAwareInterface $lineItem,
        ?string $currency
    ): ?ProductKitPriceCriteria {
        if (!$this->isSupported($lineItem, $currency)) {
            return null;
        }

        return $this->productKitLineItemPriceCriteriaBuilderFactory
            ->createFromProductLineItem($lineItem, $currency)
            ?->create();
    }

    #[\Override]
    public function isSupported(ProductLineItemInterface $lineItem, ?string $currency): bool
    {
        return $lineItem instanceof ProductKitItemLineItemsAwareInterface
            && $lineItem->getProduct()?->isKit() === true
            && $lineItem->getProductUnit() !== null
            && $lineItem->getQuantity() !== null
            && $lineItem->getQuantity() >= 0.0;
    }
}
