<?php

namespace Oro\Bundle\CheckoutBundle\Provider\MultiShipping;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Helper\CheckoutWorkflowHelper;
use Oro\Bundle\CheckoutBundle\Splitter\MultiShipping\CheckoutSplitter;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;

/**
 * Provides split checkouts to use it for totals calculation and promotions calculations.
 */
class SplitCheckoutProvider
{
    public const GROUPED_LINE_ITEMS_ATTRIBUTE = 'grouped_line_items';

    private CheckoutWorkflowHelper $checkoutWorkflowHelper;
    private CheckoutSplitter $checkoutSplitter;
    private GroupedCheckoutLineItemsProvider $groupedLineItemsProvider;
    private ConfigProvider $configProvider;

    public function __construct(
        CheckoutWorkflowHelper $checkoutWorkflowHelper,
        CheckoutSplitter $checkoutSplitter,
        GroupedCheckoutLineItemsProvider $groupedLineItemsProvider,
        ConfigProvider $configProvider
    ) {
        $this->checkoutWorkflowHelper = $checkoutWorkflowHelper;
        $this->checkoutSplitter = $checkoutSplitter;
        $this->groupedLineItemsProvider = $groupedLineItemsProvider;
        $this->configProvider = $configProvider;
    }

    /**
     * @param Checkout $checkout
     *
     * @return Checkout[] ['product.owner:1' => checkout, ...]
     */
    public function getSubCheckouts(Checkout $checkout): array
    {
        if (!$this->configProvider->isCreateSubOrdersForEachGroupEnabled()) {
            return [];
        }

        $groupedLineItems = $this->groupedLineItemsProvider->getGroupedLineItemsByIds(
            $checkout,
            $this->getGroupedLineItems($checkout)
        );
        if (empty($groupedLineItems)) {
            return [];
        }

        return $this->checkoutSplitter->split($checkout, $groupedLineItems);
    }

    private function getGroupedLineItems(Checkout $checkout): array
    {
        $workflowItem = $this->getWorkflowItem($checkout);
        if (null === $workflowItem) {
            return [];
        }

        $groupedLineItems = $workflowItem->getData()->get(self::GROUPED_LINE_ITEMS_ATTRIBUTE);
        if (!$groupedLineItems) {
            $groupedLineItems = $this->groupedLineItemsProvider->getGroupedLineItemsIds($checkout);
        }

        return $groupedLineItems;
    }

    private function getWorkflowItem(Checkout $checkout): ?WorkflowItem
    {
        $workFlowItems = $this->checkoutWorkflowHelper->findWorkflowItems($checkout);
        foreach ($workFlowItems as $workflowItem) {
            $definition = $workflowItem->getDefinition();
            if (!$definition->isActive()) {
                continue;
            }
            if (!$this->isCheckoutWorkflow($definition)) {
                continue;
            }

            // check if workflow has grouped_line_items attribute
            // to detect if line items grouping is supported by the workflow
            if ($workflowItem->getData()->has(self::GROUPED_LINE_ITEMS_ATTRIBUTE)) {
                return $workflowItem;
            }
        }

        return null;
    }

    private function isCheckoutWorkflow(WorkflowDefinition $workflowDefinition): bool
    {
        return
            $workflowDefinition->hasExclusiveRecordGroups()
            && \in_array('b2b_checkout_flow', $workflowDefinition->getExclusiveRecordGroups(), true);
    }
}
