<?php

namespace Oro\Bundle\CheckoutBundle\Layout\DataProvider\MultiShipping;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\ConfigProvider;
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
    private ConfigProvider $configProvider;
    private GroupedCheckoutLineItemsProvider $groupedLineItemsProvider;

    public function __construct(
        LineItemGroupTitleProvider $titleProvider,
        ConfigProvider $configProvider,
        GroupedCheckoutLineItemsProvider $groupedLineItemsProvider
    ) {
        $this->titleProvider = $titleProvider;
        $this->configProvider = $configProvider;
        $this->groupedLineItemsProvider = $groupedLineItemsProvider;
    }

    /**
     * Build grouped line items ids into array.
     *
     * @param WorkflowItem $workflowItem
     * @param Checkout $checkout
     * @return array
     */
    public function getGroupedLineItems(WorkflowItem $workflowItem, Checkout $checkout): array
    {
        $groupedLineItems = $this->getGroupedLineItemsFallback($workflowItem, $checkout);
        $result = [];

        foreach ($groupedLineItems as $key => $lineItemsGroup) {
            $result[$key] = array_map(
                fn (CheckoutLineItem $lineItem) => $lineItem->getId(),
                $lineItemsGroup
            );
        }

        return $result;
    }

    /**
     * Define title for each group of line items.
     *
     * @param WorkflowItem $workflowItem
     * @param Checkout $checkout
     * @return array
     */
    public function getGroupedLineItemsTitles(WorkflowItem $workflowItem, Checkout $checkout): array
    {
        $groupedLineItems = $this->getGroupedLineItemsFallback($workflowItem, $checkout);
        $titles = [];

        foreach ($groupedLineItems as $key => $lineItemsGroup) {
            $titles[$key] = $this->getGroupTitle($key, $lineItemsGroup);
        }

        return $titles;
    }

    private function getGroupedLineItemsFallback(WorkflowItem $workflowItem, Checkout $checkout)
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

    private function getGroupTitle(string $path, array $lineItems): string
    {
        $title = null;
        foreach ($lineItems as $lineItem) {
            try {
                $title = $this->titleProvider->getTitle($path, $lineItem);
                if ($title) {
                    break;
                }
            } catch (NoSuchPropertyException $exception) {
                // Skip this item and try to get title from another item from the group.
            }
        }

        if (null === $title) {
            throw new \LogicException(sprintf('Unable to get title for the checkout line items group'));
        }

        return $title;
    }
}
