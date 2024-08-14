<?php

namespace Oro\Bundle\CheckoutBundle\WorkflowState\EventListener\Workflow;

use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\CheckoutBundle\Helper\CheckoutWorkflowHelper;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\UpdateCheckoutStateInterface;
use Oro\Bundle\CheckoutBundle\WorkflowState\Manager\CheckoutStateDiffManager;
use Oro\Bundle\CheckoutBundle\WorkflowState\Storage\CheckoutDiffStorageInterface;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Oro\Bundle\WorkflowBundle\Event\Transition\GuardEvent;
use Oro\Bundle\WorkflowBundle\Event\Transition\TransitionEvent;
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

    public function initializeCurrentCheckoutState(WorkflowItemAwareEvent $event): void
    {
        if (!$this->isProtectionEnabled($event)) {
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

    public function onFormInit(TransitionEvent $event): void
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

    public function updateCheckoutState(TransitionEvent $event): void
    {
        $transitions = $this->getCheckoutStateConfig($event)['additionally_update_state_after'] ?? [];
        // Additionally update state only after configured additionally_update_state_after transitions
        if (!\in_array($event->getTransition()->getName(), (array)$transitions, true)) {
            return;
        }

        $this->doUpdateCheckoutState($event, true);
    }

    public function onPreGuard(GuardEvent $event): void
    {
        // Skip already denied
        if (!$event->isAllowed()) {
            return;
        }

        if (!$this->isProtectionEnabled($event)) {
            return;
        }

        // Protect only checkout continue transitions
        $transition = $event->getTransition();
        if (empty($transition->getFrontendOptions()['is_checkout_continue']) && !$transition->isHidden()) {
            return;
        }

        // If protect_transitions is configured - protect only listed transitions
        $protectTransitionsList = $this->getCheckoutStateConfig($event)['protect_transitions'] ?? [];
        if (!empty($protectTransitionsList)
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

    public function updateStateTokenSinglePageCheckout(WorkflowItemAwareEvent $event): void
    {
        if (!CheckoutWorkflowHelper::isSinglePageCheckoutWorkflow($event->getWorkflowItem())) {
            return;
        }

        $this->updateStateToken($event);
    }

    public function updateStateTokenMultiPageCheckout(WorkflowItemAwareEvent $event): void
    {
        if (!CheckoutWorkflowHelper::isMultiStepCheckoutWorkflow($event->getWorkflowItem())) {
            return;
        }

        $this->updateStateToken($event);
    }

    private function updateStateToken(WorkflowItemAwareEvent $event): void
    {
        if (!$this->isProtectionEnabled($event)) {
            return;
        }

        $workflowItem = $event->getWorkflowItem();
        // Do not update state_token for final steps.
        if ($workflowItem->getCurrentStep()?->isFinal()) {
            return;
        }
        $workflowItem->getData()->offsetSet('state_token', UUIDGenerator::v4());
    }

    public function deleteCheckoutState(WorkflowItemAwareEvent $event): void
    {
        if (!$this->isProtectionEnabled($event)) {
            return;
        }

        $this->diffStorage->deleteStates($event->getWorkflowItem()->getEntity());
    }

    public function deleteCheckoutStateOnStart(TransitionEvent $event): void
    {
        if (!$this->isProtectionEnabled($event)) {
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
        if (!$this->isProtectionEnabled($event)) {
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

    private function isProtectionEnabled(WorkflowItemAwareEvent $event): bool
    {
        return !empty($this->getCheckoutStateConfig($event)['enable_state_protection']);
    }

    private function getCheckoutStateConfig(WorkflowItemAwareEvent $event): array
    {
        return $event->getWorkflowItem()
            ->getDefinition()
            ?->getMetadata()['checkout_state_config'] ?? [];
    }
}
