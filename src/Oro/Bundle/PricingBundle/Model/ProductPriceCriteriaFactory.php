<?php

namespace Oro\Bundle\PricingBundle\Model;

use Oro\Bundle\PricingBundle\Model\ProductLineItemPriceCriteriaFactory\ProductLineItemPriceCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteriaBuilder\ProductPriceCriteriaBuilderInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteriaBuilder\ProductPriceCriteriaBuilderRegistry;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;

/**
 * Creates the {@see ProductPriceCriteria}.
 */
class ProductPriceCriteriaFactory implements ProductPriceCriteriaFactoryInterface
{
    private ProductPriceCriteriaBuilderRegistry $productPriceCriteriaBuilderRegistry;

    private ProductLineItemPriceCriteriaFactoryInterface $productLineItemPriceCriteriaFactory;

    public function __construct(
        ProductPriceCriteriaBuilderRegistry $productPriceCriteriaBuilderRegistry,
        ProductLineItemPriceCriteriaFactoryInterface $productLineItemPriceCriteriaFactory
    ) {
        $this->productPriceCriteriaBuilderRegistry = $productPriceCriteriaBuilderRegistry;
        $this->productLineItemPriceCriteriaFactory = $productLineItemPriceCriteriaFactory;
    }

    #[\Override]
    public function create(
        Product $product,
        ProductUnit $productUnit,
        float $quantity,
        ?string $currency = null
    ): ?ProductPriceCriteria {
        return $this->buildFromProduct($product)
            ->setProductUnit($productUnit)
            ->setQuantity($quantity)
            ->setCurrency($currency)
            ->create();
    }

    #[\Override]
    public function buildFromProduct(Product $product): ProductPriceCriteriaBuilderInterface
    {
        return $this->productPriceCriteriaBuilderRegistry
            ->getBuilderForProduct($product)
            ->setProduct($product);
    }

    #[\Override]
    public function createFromProductLineItem(
        ProductLineItemInterface $productLineItem,
        ?string $currency = null
    ): ?ProductPriceCriteria {
        return $this->productLineItemPriceCriteriaFactory->createFromProductLineItem($productLineItem, $currency);
    }

    /**
     * @param iterable<ProductLineItemInterface> $productLineItems
     *
     * @return array<int|string,ProductPriceCriteria> Products price criteria, each element associated
     *  with the key of the corresponding line item from $lineItems.
     */
    #[\Override]
    public function createListFromProductLineItems(iterable $productLineItems, ?string $currency = null): array
    {
        $productsPriceCriteria = [];
        foreach ($productLineItems as $key => $lineItem) {
            if (!$lineItem instanceof ProductLineItemInterface) {
                throw new \LogicException(
                    sprintf(
                        '$lineItems were expected to contain only %s, got %s',
                        ProductLineItemInterface::class,
                        get_debug_type($lineItem)
                    )
                );
            }

            $eachCriteria = $this->productLineItemPriceCriteriaFactory->createFromProductLineItem($lineItem, $currency);
            if ($eachCriteria !== null) {
                $productsPriceCriteria[$key] = $eachCriteria;
            }
        }

        return $productsPriceCriteria;
    }
}
