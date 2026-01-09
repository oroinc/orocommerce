<?php

namespace Oro\Bundle\CheckoutBundle\WorkflowState\EventListener\Workflow;

use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\CheckoutBundle\Event\CheckoutTransitionBeforeEvent;
use Oro\Bundle\CheckoutBundle\Helper\CheckoutWorkflowHelper;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\UpdateCheckoutStateInterface;
use Oro\Bundle\CheckoutBundle\WorkflowState\Manager\CheckoutStateDiffManager;
use Oro\Bundle\CheckoutBundle\WorkflowState\Storage\CheckoutDiffStorageInterface;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Event\Transition\PreGuardEvent;
use Oro\Bundle\WorkflowBundle\Event\Transition\TransitionCompletedEvent;
use Oro\Bundle\WorkflowBundle\Event\Transition\TransitionFormInitEvent;
use Oro\Bundle\WorkflowBundle\Event\Transition\WorkflowFinishEvent;
use Oro\Bundle\WorkflowBundle\Event\Transition\WorkflowStartEvent;
use Oro\Bundle\WorkflowBundle\Event\WorkflowItemAwareEvent;

/**
 * Works with various aspects of initialization and checking of checkout state.
 */
class CheckoutStateListener
{
    private ?array $currentCheckoutState = null;

    public function __construct(
        private ActionExecutor $actionExecutor,
        private CheckoutStateDiffManager $checkoutStateDiffManager,
        private UpdateCheckoutStateInterface $updateCheckoutStateAction,
        private CheckoutDiffStorageInterface $diffStorage
    ) {
    }

    public function initializeCurrentCheckoutState(CheckoutTransitionBeforeEvent $event): void
    {
        if (!$this->isProtectionEnabled($event->getWorkflowItem())) {
            return;
        }

        $workflowItem = $event->getWorkflowItem();
        $checkout = $workflowItem->getEntity();

        $this->currentCheckoutState = $this->checkoutStateDiffManager->getCurrentState($checkout);
    }

    public function clearCurrentCheckoutState(): void
    {
        $this->currentCheckoutState = null;
    }

    public function onFormInit(TransitionFormInitEvent $event): void
    {
        if (!CheckoutWorkflowHelper::isMultiStepCheckoutWorkflow($event->getWorkflowItem())) {
            return;
        }

        $transition = $event->getTransition();
        // Protect only checkout continue transitions
        if (empty($transition->getFrontendOptions()['is_checkout_continue'])) {
            return;
        }

        $this->doUpdateCheckoutState($event);
    }

    public function updateCheckoutState(TransitionCompletedEvent $event): void
    {
        $transitions = $this
            ->getCheckoutStateConfig($event->getWorkflowItem())['additionally_update_state_after'] ?? [];
        // Additionally update state only after configured additionally_update_state_after transitions
        if (!\in_array($event->getTransition()->getName(), (array)$transitions, true)) {
            return;
        }

        $this->doUpdateCheckoutState($event, true);
    }

    public function onPreGuard(PreGuardEvent $event): void
    {
        // Skip already denied
        if (!$event->isAllowed()) {
            return;
        }

        if (!$this->isProtectionEnabled($event->getWorkflowItem())) {
            return;
        }

        // Protect only checkout continue transitions
        $transition = $event->getTransition();
        if (empty($transition->getFrontendOptions()['is_checkout_continue']) && !$transition->isHidden()) {
            return;
        }

        // If protect_transitions is configured - protect only listed transitions
        $protectTransitionsList = $this->getCheckoutStateConfig($event->getWorkflowItem())['protect_transitions'] ?? [];
        if (
            !empty($protectTransitionsList)
            && !\in_array($event->getTransition()->getName(), $protectTransitionsList, true)
        ) {
            return;
        }

        $workflowItem = $event->getWorkflowItem();
        $workflowData = $workflowItem->getData();

        $isAllowed = $this->actionExecutor->evaluateExpression(
            'is_checkout_state_valid',
            [
                'entity' => $workflowItem->getEntity(),
                'token' => $workflowData['state_token'],
                'current_state' => $this->currentCheckoutState
            ],
            $event->getErrors(),
            'oro.checkout.workflow.condition.content_of_order_was_changed.message'
        );

        $event->setAllowed($isAllowed);
    }

    public function updateStateTokenSinglePageCheckout(WorkflowStartEvent $event): void
    {
        if (!CheckoutWorkflowHelper::isSinglePageCheckoutWorkflow($event->getWorkflowItem())) {
            return;
        }

        $this->updateStateToken($event);
    }

    public function updateStateTokenMultiPageCheckout(TransitionCompletedEvent $event): void
    {
        if (!CheckoutWorkflowHelper::isMultiStepCheckoutWorkflow($event->getWorkflowItem())) {
            return;
        }

        $this->updateStateToken($event);
    }

    private function updateStateToken(WorkflowItemAwareEvent $event): void
    {
        if (!$this->isProtectionEnabled($event->getWorkflowItem())) {
            return;
        }

        $workflowItem = $event->getWorkflowItem();
        // Do not update state_token for final steps.
        if ($workflowItem->getCurrentStep()?->isFinal()) {
            return;
        }
        $workflowItem->getData()->offsetSet('state_token', UUIDGenerator::v4());
    }

    public function deleteCheckoutState(WorkflowFinishEvent $event): void
    {
        if (!$this->isProtectionEnabled($event->getWorkflowItem())) {
            return;
        }

        $this->diffStorage->deleteStates($event->getWorkflowItem()->getEntity());
    }

    public function deleteCheckoutStateOnStart(TransitionCompletedEvent $event): void
    {
        if (!$this->isProtectionEnabled($event->getWorkflowItem())) {
            return;
        }

        if (!$event->getTransition()->isStart()) {
            return;
        }

        $stateToken = $event->getWorkflowItem()->getData()->offsetGet('state_token');
        if (!$stateToken) {
            return;
        }

        $this->diffStorage->deleteStates(
            $event->getWorkflowItem()->getEntity(),
            $stateToken
        );
    }

    private function doUpdateCheckoutState(WorkflowItemAwareEvent $event, bool $forceUpdate = false): void
    {
        if (!$this->isProtectionEnabled($event->getWorkflowItem())) {
            return;
        }

        $workflowItem = $event->getWorkflowItem();
        $checkout = $workflowItem->getEntity();
        $workflowData = $workflowItem->getData();

        $updateData = $this->updateCheckoutStateAction->execute(
            $checkout,
            $workflowData['state_token'],
            (bool)$workflowItem->getResult()->offsetGet('updateCheckoutState'),
            $forceUpdate
        );
        $workflowItem->getResult()->offsetSet('updateCheckoutState', $updateData);
    }

    private function isProtectionEnabled(WorkflowItem $workflowItem): bool
    {
        return !empty($this->getCheckoutStateConfig($workflowItem)['enable_state_protection']);
    }

    private function getCheckoutStateConfig(WorkflowItem $workflowItem): array
    {
        return $workflowItem->getDefinition()
            ?->getMetadata()['checkout_state_config'] ?? [];
    }
}
