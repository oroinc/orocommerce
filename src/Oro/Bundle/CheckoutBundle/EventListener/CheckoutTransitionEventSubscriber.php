<?php

namespace Oro\Bundle\CheckoutBundle\EventListener;

use Oro\Bundle\CheckoutBundle\Event\CheckoutTransitionAfterEvent;
use Oro\Bundle\CheckoutBundle\Event\CheckoutTransitionBeforeEvent;
use Oro\Bundle\CheckoutBundle\WorkflowState\Manager\CheckoutStateDiffManager;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Adds currentCheckoutState to workflow item result before transition.
 * Removes currentCheckoutState after transition because it is not needed anymore.
 * Makes a correct checkout state which is generated before transition form is submitted.
 *
 * Subscriber is disabled when workflow is managed by the CheckoutStateListener.
 */
class CheckoutTransitionEventSubscriber implements EventSubscriberInterface
{
    /** @var CheckoutStateDiffManager */
    private $checkoutStateDiffManager;

    public function __construct(CheckoutStateDiffManager $checkoutStateDiffManager)
    {
        $this->checkoutStateDiffManager = $checkoutStateDiffManager;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutTransitionBeforeEvent::class => 'onBefore',
            CheckoutTransitionAfterEvent::class => 'onAfter',
        ];
    }

    public function onBefore(CheckoutTransitionBeforeEvent $event): void
    {
        $workflowItem = $event->getWorkflowItem();
        if (!$this->isSupported($workflowItem)) {
            return;
        }

        $checkout = $workflowItem->getEntity();
        $currentState = $this->checkoutStateDiffManager->getCurrentState($checkout);
        $workflowItem->getResult()->set('currentCheckoutState', $currentState);
    }

    public function onAfter(CheckoutTransitionAfterEvent $event): void
    {
        $workflowItem = $event->getWorkflowItem();
        if (!$this->isSupported($workflowItem)) {
            return;
        }

        $workflowItem->getResult()->remove('currentCheckoutState');
    }

    private function isSupported(WorkflowItem $workflowItem): bool
    {
        $metadata = $workflowItem->getDefinition()?->getMetadata();

        return !is_array($metadata) || !array_key_exists('checkout_state_config', $metadata);
    }
}
