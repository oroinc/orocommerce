<?php

namespace Oro\Bundle\CheckoutBundle\Helper;

use Oro\Bundle\CheckoutBundle\Entity\CheckoutInterface;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

/**
 * Use it to process checkout workflow
 */
class CheckoutWorkflowHelper
{
    private array $workflowItems = [];

    public function __construct(
        private WorkflowManager $workflowManager
    ) {
    }

    public static function isSinglePageCheckoutWorkflow(WorkflowItem $workflowItem): bool
    {
        $metadata = $workflowItem->getDefinition()?->getMetadata();

        return self::isCheckoutWorkflow($workflowItem)
            && !empty($metadata['is_single_page_checkout']);
    }

    public static function isMultiStepCheckoutWorkflow(WorkflowItem $workflowItem): bool
    {
        $metadata = $workflowItem->getDefinition()?->getMetadata();

        return self::isCheckoutWorkflow($workflowItem)
            && empty($metadata['is_single_page_checkout']);
    }

    public static function isCheckoutWorkflow(WorkflowItem $workflowItem): bool
    {
        $metadata = $workflowItem->getDefinition()?->getMetadata();

        return !empty($metadata['is_checkout_workflow']);
    }

    public function getWorkflowItem(CheckoutInterface $checkout): ?WorkflowItem
    {
        $items = $this->findWorkflowItems($checkout);
        if (\count($items) !== 1) {
            return null;
        }

        return reset($items);
    }

    /**
     * @param CheckoutInterface $checkout
     *
     * @return WorkflowItem[]
     */
    public function findWorkflowItems(CheckoutInterface $checkout): array
    {
        $checkoutId = $checkout->getId();
        if (!isset($this->workflowItems[$checkoutId])) {
            $this->workflowItems[$checkoutId] = $this->workflowManager->getWorkflowItemsByEntity($checkout);
        }

        return $this->workflowItems[$checkoutId];
    }

    public function clearCaches(CheckoutInterface $checkout): void
    {
        unset($this->workflowItems[$checkout->getId()]);
    }
}
