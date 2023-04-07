<?php

namespace Oro\Bundle\PricingBundle\Provider;

use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsAwareInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsNotPricedAwareInterface;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;
use Oro\Bundle\ProductBundle\Model\ProductKitItemLineItemsAwareInterface;
use Oro\Bundle\ProductBundle\Model\ProductUnitHolderInterface;
use Oro\Bundle\ProductBundle\Model\QuantityAwareInterface;

/**
 * Provides products price criteria for the line items holder.
 */
class ProductLineItemsHolderPriceCriteriaProvider
{
    private ProductLineItemsHolderCurrencyProvider $productLineItemsHolderCurrencyProvider;

    public function __construct(ProductLineItemsHolderCurrencyProvider $productLineItemsHolderCurrencyProvider)
    {
        $this->productLineItemsHolderCurrencyProvider = $productLineItemsHolderCurrencyProvider;
    }

    /**
     * @param LineItemsAwareInterface|LineItemsNotPricedAwareInterface $lineItemsHolder
     * @param string|null $currency
     *
     * @return array<string,ProductPriceCriteria> Products price criteria indexed by the line item object hash.
     */
    public function getProductPriceCriteriaForLineItemsHolder(
        LineItemsAwareInterface|LineItemsNotPricedAwareInterface $lineItemsHolder,
        ?string $currency = null
    ): array {
        if ($currency === null) {
            $currency = $this->productLineItemsHolderCurrencyProvider->getCurrencyForLineItemsHolder($lineItemsHolder);
        }

        $productsPriceCriteria = [];
        foreach ($this->getLineItems($lineItemsHolder) as $lineItem) {
            $product = $lineItem->getProduct();
            $productUnit = $lineItem->getProductUnit();
            if ($productUnit?->getCode() && $product?->getId() && $product->isEnabled()) {
                $criteria = new ProductPriceCriteria(
                    $product,
                    $productUnit,
                    (float)$lineItem->getQuantity(),
                    $currency
                );

                $productsPriceCriteria[spl_object_hash($lineItem)] = $criteria;
            }
        }

        return $productsPriceCriteria;
    }

    /**
     * @param LineItemsAwareInterface|LineItemsNotPricedAwareInterface $lineItemsHolder
     * @return \Generator<ProductHolderInterface&ProductUnitHolderInterface&QuantityAwareInterface>
     */
    private function getLineItems(
        LineItemsAwareInterface|LineItemsNotPricedAwareInterface $lineItemsHolder
    ): \Generator {
        foreach ($lineItemsHolder->getLineItems() as $lineItem) {
            if ($lineItem instanceof ProductKitItemLineItemsAwareInterface && $lineItem->getKitItemLineItems()) {
                foreach ($lineItem->getKitItemLineItems() as $kitItemLineItem) {
                    if ($this->isSupported($kitItemLineItem)) {
                        yield $kitItemLineItem;
                    }
                }
            }

            if ($this->isSupported($lineItem)) {
                yield $lineItem;
            }
        }
    }

    private function isSupported(object $lineItem): bool
    {
        return $lineItem instanceof ProductHolderInterface
            && $lineItem instanceof ProductUnitHolderInterface
            && $lineItem instanceof QuantityAwareInterface;
    }
}
