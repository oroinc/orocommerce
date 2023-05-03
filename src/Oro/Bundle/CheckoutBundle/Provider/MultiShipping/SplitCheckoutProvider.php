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

    private ManagerRegistry $doctrine;
    private CheckoutSplitter $checkoutSplitter;
    private GroupedCheckoutLineItemsProvider $groupedLineItemsProvider;
    private ConfigProvider $configProvider;
    private array $subCheckouts = [];

    public function __construct(
        ManagerRegistry $doctrine,
        CheckoutSplitter $checkoutSplitter,
        GroupedCheckoutLineItemsProvider $groupedLineItemsProvider,
        ConfigProvider $configProvider
    ) {
        $this->doctrine = $doctrine;
        $this->checkoutSplitter = $checkoutSplitter;
        $this->groupedLineItemsProvider = $groupedLineItemsProvider;
        $this->configProvider = $configProvider;
    }

    /**
     * @param Checkout $checkout
     * @param bool     $useCache
     *
     * @return Checkout[] ['product.owner:1' => checkout, ...]
     */
    public function getSubCheckouts(Checkout $checkout, bool $useCache = true): array
    {
        if (!$useCache) {
            return $this->splitCheckout($checkout);
        }

        $cacheKey = $checkout->getId();
        if (!\array_key_exists($cacheKey, $this->subCheckouts)) {
            $this->subCheckouts[$cacheKey] = $this->splitCheckout($checkout);
        }

        return $this->subCheckouts[$cacheKey];
    }

    private function isCreateSubOrdersEnabled(): bool
    {
        return $this->configProvider->isCreateSubOrdersForEachGroupEnabled();
    }

    /**
     * @param Checkout $checkout
     *
     * @return Checkout[] ['product.owner:1' => checkout, ...]
     */
    private function splitCheckout(Checkout $checkout): array
    {
        if (!$this->isCreateSubOrdersEnabled()) {
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

    /**
     * Try to get grouped_line_items from workflow data attribute or use provider if attribute is empty.
     */
    private function getGroupedLineItems(Checkout $checkout): array
    {
        $workflowItems = $this->doctrine->getRepository(WorkflowItem::class)
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
        return
            $workflowDefinition->hasExclusiveRecordGroups()
            && \in_array('b2b_checkout_flow', $workflowDefinition->getExclusiveRecordGroups(), true);
    }
}
