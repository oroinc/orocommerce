<?php

declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\Model\ProductLineItemPriceCriteriaFactory;

use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteriaBuilder\ProductPriceCriteriaBuilderInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;

/**
 * Creates a product line item price criteria for the specified product line item.
 */
class SimpleProductLineItemPriceCriteriaFactory implements ProductLineItemPriceCriteriaFactoryInterface
{
    private ProductPriceCriteriaBuilderInterface $productPriceCriteriaBuilder;

    public function __construct(ProductPriceCriteriaBuilderInterface $productPriceCriteriaBuilder)
    {
        $this->productPriceCriteriaBuilder = $productPriceCriteriaBuilder;
    }

    #[\Override]
    public function createFromProductLineItem(
        ProductLineItemInterface $lineItem,
        ?string $currency
    ): ?ProductPriceCriteria {
        return $this->productPriceCriteriaBuilder
            ->setProduct($lineItem->getProduct())
            ->setProductUnit($lineItem->getProductUnit())
            ->setQuantity((float)$lineItem->getQuantity())
            ->setCurrency($currency)
            ->create();
    }

    #[\Override]
    public function isSupported(ProductLineItemInterface $lineItem, ?string $currency): bool
    {
        return $lineItem->getProduct() !== null
            && $lineItem->getProductUnit() !== null
            && $lineItem->getQuantity() !== null
            && $lineItem->getQuantity() >= 0.0;
    }
}
