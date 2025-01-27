<?php

namespace Oro\Bundle\CheckoutBundle\EventListener;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Event\CheckoutTransitionBeforeEvent;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListLimitManager;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Determines what will happen to the source after the checkout is completed.
 */
class CheckoutTransitionSourceConfiguration implements EventSubscriberInterface
{
    public function __construct(private ShoppingListLimitManager $shoppingListLimitManager)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [CheckoutTransitionBeforeEvent::class => 'onBefore'];
    }

    public function onBefore(CheckoutTransitionBeforeEvent $event): void
    {
        $workflowItem = $event->getWorkflowItem();
        $workflowData = $workflowItem->getData();

        if (!$this->checkoutSourceSupported($workflowItem)) {
            return;
        }

        $isOnlyOneEnabled = $this->shoppingListLimitManager->isOnlyOneEnabled();
        $this
            ->setWorkflowDataValue($workflowData, 'allow_manual_source_remove', !$isOnlyOneEnabled)
            ->setWorkflowDataValue($workflowData, 'remove_source', !$isOnlyOneEnabled)
            ->setWorkflowDataValue($workflowData, 'clear_source', $isOnlyOneEnabled);

        $event->getWorkflowItem()->setData($workflowData);
    }

    /**
     * We can't delete the shopping list data if the checkout is based without it, for example, a re-order,
     * so we'll limit ourselves to the shopping list as a source.
     */
    private function checkoutSourceSupported(WorkflowItem $workflowItem): bool
    {
        /** @var Checkout $checkout */
        $checkout = $workflowItem->getEntity();
        $sourceEntity = $checkout->getSource()->getEntity();

        return $sourceEntity instanceof ShoppingList;
    }

    private function setWorkflowDataValue(WorkflowData $workflowData, string $key, bool $value): self
    {
        if ($workflowData->has($key)) {
            $workflowData->set($key, $value);
        }

        return $this;
    }
}
