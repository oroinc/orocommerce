<?php

namespace Oro\Bundle\CheckoutBundle\Layout\DataProvider\MultiShipping;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\GroupedCheckoutLineItemsProvider;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\LineItemGroupTitleProvider;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;

/**
 * Provides line items grouping data.
 */
class GroupLineItemsDataProvider
{
    private const GROUPED_LINE_ITEMS_ATTRIBUTE = 'grouped_line_items';

    private LineItemGroupTitleProvider $titleProvider;
    private GroupedCheckoutLineItemsProvider $groupedLineItemsProvider;

    public function __construct(
        LineItemGroupTitleProvider $titleProvider,
        GroupedCheckoutLineItemsProvider $groupedLineItemsProvider
    ) {
        $this->titleProvider = $titleProvider;
        $this->groupedLineItemsProvider = $groupedLineItemsProvider;
    }

    /**
     * Build grouped line items ids into array.
     */
    public function getGroupedLineItems(WorkflowItem $workflowItem, Checkout $checkout): array
    {
        $result = [];
        $groupedLineItems = $this->getGroupedLineItemsFallback($workflowItem, $checkout);
        foreach ($groupedLineItems as $lineItemGroupKey => $lineItems) {
            $result[$lineItemGroupKey] = array_map(
                fn (CheckoutLineItem $lineItem) => $lineItem->getId(),
                $lineItems
            );
        }

        return $result;
    }

    /**
     * Define title for each group of line items.
     */
    public function getGroupedLineItemsTitles(WorkflowItem $workflowItem, Checkout $checkout): array
    {
        $titles = [];
        $groupedLineItems = $this->getGroupedLineItemsFallback($workflowItem, $checkout);
        foreach ($groupedLineItems as $lineItemGroupKey => $lineItems) {
            $titles[$lineItemGroupKey] = $this->getGroupTitle($lineItemGroupKey, $lineItems);
        }

        return $titles;
    }

    private function getGroupedLineItemsFallback(WorkflowItem $workflowItem, Checkout $checkout): array
    {
        $workflowData = $workflowItem->getData();
        if ($workflowData->has(self::GROUPED_LINE_ITEMS_ATTRIBUTE)) {
            $groupedLineItemsData = $workflowData->get(self::GROUPED_LINE_ITEMS_ATTRIBUTE);
            if (!empty($groupedLineItemsData)) {
                return $this->groupedLineItemsProvider->getGroupedLineItemsByIds($checkout, $groupedLineItemsData);
            }
        }

        return $this->groupedLineItemsProvider->getGroupedLineItems($checkout);
    }

    private function getGroupTitle(string $lineItemGroupKey, array $lineItems): string
    {
        $title = null;
        foreach ($lineItems as $lineItem) {
            try {
                $title = $this->titleProvider->getTitle($lineItemGroupKey, $lineItem);
                if ($title) {
                    break;
                }
            } catch (NoSuchPropertyException) {
                // Skip this item and try to get title from another item from the group.
            }
        }

        if (null === $title) {
            throw new \LogicException(sprintf(
                'Unable to get title for the checkout line items group "%s".',
                $lineItemGroupKey
            ));
        }

        return $title;
    }
}
