<?php

namespace Oro\Bundle\CheckoutBundle\Provider\MultiShipping;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Factory\MultiShipping\CheckoutFactoryInterface;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutLineItemsProvider;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\LineItemsGrouping\GroupedLineItemsProviderInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;

/**
 * Provides grouped line items data.
 */
class GroupedCheckoutLineItemsProvider
{
    private GroupedLineItemsProviderInterface $groupingService;
    private CheckoutLineItemsProvider $checkoutLineItemsProvider;
    private CheckoutFactoryInterface $checkoutFactory;

    public function __construct(
        GroupedLineItemsProviderInterface $groupingService,
        CheckoutLineItemsProvider $checkoutLineItemsProvider,
        CheckoutFactoryInterface $checkoutFactory
    ) {
        $this->groupingService = $groupingService;
        $this->checkoutLineItemsProvider = $checkoutLineItemsProvider;
        $this->checkoutFactory = $checkoutFactory;
    }

    /**
     * Groups checkout line items by provided grouped line items ids.
     *
     * @param Checkout $sourceCheckout
     *
     * @return array [product.owner:1 => [checkout line item, ...], ...]
     */
    public function getGroupedLineItems(Checkout $sourceCheckout): array
    {
        // Prepare checkout with line items which could be converted to OrderLineItems only.
        $checkoutLineItems = $this->checkoutLineItemsProvider->getCheckoutLineItems($sourceCheckout);
        $checkout = $this->checkoutFactory->createCheckout($sourceCheckout, $checkoutLineItems->toArray());

        return $this->groupingService->getGroupedLineItems($checkout);
    }

    /**
     * Groups checkout line items and represents data as associative array of its ids.
     *
     * @param Checkout $sourceCheckout
     *
     * @return array ['product.owner:1' => ['sku-1:item','sku-2:item'], ...]
     */
    public function getGroupedLineItemsIds(Checkout $sourceCheckout): array
    {
        $result = [];
        $groupedLineItems = $this->getGroupedLineItems($sourceCheckout);
        foreach ($groupedLineItems as $groupingPath => $lineItems) {
            $group = [];
            foreach ($lineItems as $lineItem) {
                $group[] = $this->getLineItemKey($lineItem);
            }
            $result[$groupingPath] = $group;
        }

        return $result;
    }

    /**
     * Groups checkout line items by provided grouped line items ids.
     *
     * @param Checkout $checkout
     * @param array    $groupedItemsIds ['product.owner:1' => ['sku-1:item', ...], ...]
     *
     * @return array ['product.owner:1' => [checkout line item, ...], ...]
     */
    public function getGroupedLineItemsByIds(Checkout $checkout, array $groupedItemsIds): array
    {
        $splitItems = [];
        $lineItems = $checkout->getLineItems();
        foreach ($groupedItemsIds as $groupingPath => $lineItemsIds) {
            $groupSplitItems = [];
            foreach ($lineItems as $lineItem) {
                if (\in_array($this->getLineItemKey($lineItem), $lineItemsIds, true)) {
                    $groupSplitItems[] = $lineItem;
                }
            }
            $splitItems[$groupingPath] = $groupSplitItems;
        }

        return $splitItems;
    }

    private function getLineItemKey(ProductLineItemInterface $item): string
    {
        return sprintf('%s:%s', $item->getProductSku(), $item->getProductUnitCode());
    }
}
