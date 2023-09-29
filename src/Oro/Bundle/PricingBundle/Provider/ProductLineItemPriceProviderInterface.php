<?php

namespace Oro\Bundle\PricingBundle\Provider;

use Oro\Bundle\PricingBundle\Model\ProductLineItemPrice\ProductLineItemPrice;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsAwareInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsNotPricedAwareInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemsHolderInterface;

/**
 * Interface for the providers of product line item prices.
 */
interface ProductLineItemPriceProviderInterface
{
    /**
     * Provides {@see ProductLineItemPrice} objects for the specified $lineItems.
     *
     * @param iterable<ProductLineItemInterface> $lineItems
     * @param string|null $currency When null - currency is detected automatically from the current context.
     * @param ProductPriceScopeCriteriaInterface|null $priceScopeCriteria
     *
     * @return array<int|string,ProductLineItemPrice> Array of product line item prices, each element
     *  associated with the key of the corresponding line item from $lineItems
     */
    public function getProductLineItemsPrices(
        iterable $lineItems,
        ?ProductPriceScopeCriteriaInterface $priceScopeCriteria = null,
        ?string $currency = null
    ): array;

    /**
     * Provides an array of {@see ProductLineItemPrice} objects for the specified $lineItemsHolder.
     *
     * @param ProductLineItemsHolderInterface|LineItemsAwareInterface|LineItemsNotPricedAwareInterface $lineItemsHolder
     * @param string|null $currency When null - currency is detected automatically by $lineItemsHolder.
     *
     * @return array<int|string,ProductLineItemPrice> Array of product line item prices, each element
     *  associated with the key of the corresponding line item from $lineItemsHolder::getLineItems()
     */
    public function getProductLineItemsPricesForLineItemsHolder(
        ProductLineItemsHolderInterface|LineItemsAwareInterface|LineItemsNotPricedAwareInterface $lineItemsHolder,
        ?string $currency = null
    ): array;
}
