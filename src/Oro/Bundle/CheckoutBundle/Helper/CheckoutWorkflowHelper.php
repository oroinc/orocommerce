<?php

namespace Oro\Bundle\CheckoutBundle\Helper;

use Oro\Bundle\CheckoutBundle\Entity\CheckoutInterface;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
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
        return self::isSinglePageCheckoutWorkflowDefinition($workflowItem->getDefinition());
    }

    public static function isSinglePageCheckoutWorkflowDefinition(?WorkflowDefinition $definition): bool
    {
        return self::isCheckoutWorkflowDefinition($definition)
            && self::checkMetadataKey($definition, 'is_single_page_checkout');
    }

    public static function isMultiStepCheckoutWorkflow(WorkflowItem $workflowItem): bool
    {
        return self::isMultiStepCheckoutWorkflowDefinition($workflowItem->getDefinition());
    }

    public static function isMultiStepCheckoutWorkflowDefinition(?WorkflowDefinition $definition): bool
    {
        return self::isCheckoutWorkflowDefinition($definition)
            && !self::checkMetadataKey($definition, 'is_single_page_checkout');
    }

    public static function isCheckoutWorkflow(WorkflowItem $workflowItem): bool
    {
        return self::isCheckoutWorkflowDefinition($workflowItem->getDefinition());
    }

    public static function isCheckoutWorkflowDefinition(?WorkflowDefinition $definition): bool
    {
        return self::checkMetadataKey($definition, 'is_checkout_workflow');
    }

    private static function checkMetadataKey(?WorkflowDefinition $definition, string $key): bool
    {
        if (!$definition) {
            return false;
        }

        return !empty($definition?->getMetadata()[$key]);
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
