<?php

namespace Oro\Bundle\CheckoutBundle\Provider\MultiShipping;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Splitter\MultiShipping\CheckoutSplitter;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;

/**
 * Provides split checkouts to use it for totals calculation and promotions calculations.
 */
class SplitCheckoutProvider
{
    public const GROUPED_LINE_ITEMS_ATTRIBUTE = 'grouped_line_items';

    private ManagerRegistry $managerRegistry;
    private CheckoutSplitter $checkoutSplitter;
    private GroupedCheckoutLineItemsProvider $groupedLineItemsProvider;
    private ConfigProvider $configProvider;

    private array $subCheckouts = [];

    public function __construct(
        ManagerRegistry $managerRegistry,
        CheckoutSplitter $checkoutSplitter,
        GroupedCheckoutLineItemsProvider $groupedLineItemsProvider,
        ConfigProvider $configProvider
    ) {
        $this->managerRegistry = $managerRegistry;
        $this->checkoutSplitter = $checkoutSplitter;
        $this->groupedLineItemsProvider = $groupedLineItemsProvider;
        $this->configProvider = $configProvider;
    }

    public function getSubCheckouts(Checkout $checkout, $useCache = true): array
    {
        if (!$useCache) {
            return $this->splitCheckout($checkout);
        }

        $cacheKey = $checkout->getId();
        if (!array_key_exists($cacheKey, $this->subCheckouts)) {
            $this->subCheckouts[$cacheKey] = $this->splitCheckout($checkout);
        }

        return $this->subCheckouts[$cacheKey];
    }

    private function isCreateSubOrdersEnabled(): bool
    {
        return $this->configProvider->isCreateSubOrdersForEachGroupEnabled();
    }

    private function splitCheckout(Checkout $checkout): array
    {
        if (!$this->isCreateSubOrdersEnabled()) {
            return [];
        }

        $groupedLineItemsIds = $this->getGroupedLineItems($checkout);
        $groupedLineItems = $this->groupedLineItemsProvider->getGroupedLineItemsByIds($checkout, $groupedLineItemsIds);

        if (empty($groupedLineItems)) {
            return [];
        }

        return $this->checkoutSplitter->split($checkout, $groupedLineItems);
    }

    /**
     * Try to get grouped_line_items from workflow data attribute or use provider if attribute is empty.
     *
     * @param Checkout $checkout
     * @return array
     * @throws \Oro\Bundle\WorkflowBundle\Exception\WorkflowException
     */
    private function getGroupedLineItems(Checkout $checkout): array
    {
        $workflowItems = $this->managerRegistry->getRepository(WorkflowItem::class)
            ->findAllByEntityMetadata(ClassUtils::getClass($checkout), $checkout->getId());

        $workflowItem = $this->filterWorkflowItems($workflowItems);

        if (!$workflowItem) {
            return [];
        }

        $workflowData = $workflowItem->getData();

        return !empty($workflowData->get(self::GROUPED_LINE_ITEMS_ATTRIBUTE))
            ? $workflowData->get(self::GROUPED_LINE_ITEMS_ATTRIBUTE)
            : $this->groupedLineItemsProvider->getGroupedLineItemsIds($checkout);
    }

    /**
     * Check if there is a workflow which support line items grouping.
     *
     * @param array $workFlowItems
     */
    private function filterWorkflowItems(array $workFlowItems): ?WorkflowItem
    {
        /** @var WorkflowItem $workflowItem */
        foreach ($workFlowItems as $workflowItem) {
            $definition = $workflowItem->getDefinition();

            // Check if workflow is active.
            if (!$definition->isActive()) {
                continue;
            }

            // Check if workflow is checkout.
            if (!$this->isCheckoutWorkflow($definition)) {
                continue;
            }

            // Check if workflow has grouped_line_items attribute to detect if line items grouping is supported by
            // workflow.
            if ($workflowItem->getData()->has(self::GROUPED_LINE_ITEMS_ATTRIBUTE)) {
                return $workflowItem;
            }
        }

        return null;
    }

    private function isCheckoutWorkflow(WorkflowDefinition $workflowDefinition): bool
    {
        return $workflowDefinition->hasExclusiveRecordGroups()
            && in_array('b2b_checkout_flow', $workflowDefinition->getExclusiveRecordGroups());
    }
}
