<?php

namespace Oro\Bundle\CheckoutBundle\Provider\MultiShipping;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Factory\MultiShipping\CheckoutFactoryInterface;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutLineItemsProvider;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\LineItemsGrouping\GroupedLineItemsProviderInterface;
use Oro\Bundle\ProductBundle\Model\ProductKitItemLineItemsAwareInterface;
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
     * Groups checkout line items.
     *
     * @param Checkout $checkout
     *
     * @return array [product.owner:1 => [checkout line item, ...], ...]
     */
    public function getGroupedLineItems(Checkout $checkout): array
    {
        return $this->groupingService->getGroupedLineItems(
            $this->checkoutFactory->createCheckout(
                $checkout,
                $this->checkoutLineItemsProvider->getCheckoutLineItems($checkout)
            )
        );
    }

    /**
     * Groups checkout line items and represents data as associative array of its ids.
     *
     * @param Checkout $checkout
     *
     * @return array ['product.owner:1' => ['sku-1:item','sku-2:item'], ...]
     */
    public function getGroupedLineItemsIds(Checkout $checkout): array
    {
        $result = [];
        $groupedLineItems = $this->getGroupedLineItems($checkout);
        foreach ($groupedLineItems as $lineItemGroupKey => $lineItems) {
            $group = [];
            foreach ($lineItems as $lineItem) {
                $group[] = $this->getLineItemKey($lineItem);
            }
            $result[$lineItemGroupKey] = $group;
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
        foreach ($groupedItemsIds as $lineItemGroupKey => $lineItemsIds) {
            $groupSplitItems = [];
            foreach ($lineItems as $lineItem) {
                if (\in_array($this->getLineItemKey($lineItem), $lineItemsIds, true)) {
                    $groupSplitItems[] = $lineItem;
                }
            }
            $splitItems[$lineItemGroupKey] = $groupSplitItems;
        }

        return $splitItems;
    }

    private function getLineItemKey(ProductLineItemInterface $lineItem): string
    {
        $key = implode(':', [$lineItem->getProductSku(), $lineItem->getProductUnitCode()]);
        if ($lineItem instanceof ProductKitItemLineItemsAwareInterface) {
            $key .= ':' . $lineItem->getChecksum();
        }

        return $key;
    }
}
