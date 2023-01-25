<?php

namespace Oro\Bundle\CheckoutBundle\Provider\MultiShipping;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Factory\MultiShipping\CheckoutFactoryInterface;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutLineItemsProvider;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\LineItemsGrouping\GroupedLineItemsProviderInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;

/**
 * Incapsulates logic to get grouped line items data.
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
     * Group checkout line items by provided grouped line items ids.
     *
     * @param Checkout $sourceCheckout
     * @return array
     *      [
     *          product.owner:1 => [
     *              <Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem>,
     *              <Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem>
     *          ],
     *          product.owner:2 => [
     *              <Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem>
     *          ]
     *      ]
     */
    public function getGroupedLineItems(Checkout $sourceCheckout): array
    {
        // Prepare checkout with line items which could be converted to OrderLineItems only.
        $checkoutLineItems = $this->checkoutLineItemsProvider->getCheckoutLineItems($sourceCheckout);
        $checkout = $this->checkoutFactory->createCheckout($sourceCheckout, $checkoutLineItems->toArray());

        return $this->groupingService->getGroupedLineItems($checkout);
    }

    /**
     * Checkout Line items grouping and represent data as associative array of its ids.
     *
     * @param Checkout $sourceCheckout
     * @return array [
     *                  'product.owner:1' => ['sku-1:item','sku-2:item'],
     *                  'product.owner:2' => ['sku-4:set','sku-5:item']
     *                  ...
     *               ]
     */
    public function getGroupedLineItemsIds(Checkout $sourceCheckout): array
    {
        $groupedLineItems = $this->getGroupedLineItems($sourceCheckout);
        $result = [];

        foreach ($groupedLineItems as $key => $lineItemsGroup) {
            $result[$key] = array_map([$this, 'getLineItemKey'], $lineItemsGroup);
        }

        return $result;
    }

    /**
     * Group checkout line items by provided grouped line items ids.
     *
     * @param Checkout $checkout
     * @param array $groupedItemsIds
     * @return array
     *      [
     *          product.owner:1 => [
     *              <Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem>,
     *              <Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem>
     *          ],
     *          product.owner:2 => [
     *              <Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem>
     *          ]
     *      ]
     */
    public function getGroupedLineItemsByIds(Checkout $checkout, array $groupedItemsIds): array
    {
        $lineItems = $checkout->getLineItems();
        $splitItems = [];

        foreach ($groupedItemsIds as $key => $lineItemsGroupIds) {
            $splitItems[$key] = $lineItems->filter(
                fn (CheckoutLineItem $lineItem) => in_array($this->getLineItemKey($lineItem), $lineItemsGroupIds)
            )->toArray();

            if (!empty($lineItemsGroup)) {
                $splitItems[$key] = $lineItemsGroup;
            }
        }

        return $splitItems;
    }

    private function getLineItemKey(ProductLineItemInterface $item): string
    {
        return implode(':', [$item->getProductSku(), $item->getProductUnitCode()]);
    }
}
