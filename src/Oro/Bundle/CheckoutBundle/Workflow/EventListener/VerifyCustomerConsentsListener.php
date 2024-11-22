<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\EventListener;

use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\CheckoutBundle\Event\CheckoutRequestEvent;
use Oro\Bundle\CheckoutBundle\Helper\CheckoutWorkflowHelper;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerAwareInterface;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Event\Transition\StepEnteredEvent;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Verify customer consents accepted otherwise run verify_customer_consents transition.
 */
class VerifyCustomerConsentsListener implements FeatureCheckerAwareInterface
{
    use FeatureCheckerHolderTrait;

    protected const TRANSITION_NAME = 'verify_customer_consents';
    protected const CUSTOMER_CONSENTS_STEP = 'customer_consents';
    protected const CHECKOUT_GROUP = 'b2b_checkout_flow';

    public function __construct(
        private ActionExecutor $actionExecutor,
        private CheckoutWorkflowHelper $checkoutWorkflowHelper,
        private WorkflowManager $workflowManager,
        private TokenStorageInterface $tokenStorage
    ) {
    }

    public function checkConsents(CheckoutRequestEvent $event): void
    {
        if (!$this->isFeaturesEnabled()) {
            return;
        }

        $workflowItem = $this->checkoutWorkflowHelper->getWorkflowItem($event->getCheckout());
        if (!$this->isApplicable($workflowItem)) {
            return;
        }

        $isConsentsAccepted = false;
        if (!$workflowItem->getResult()->offsetGet('isConsentsAccepted')) {
            $isConsentsAccepted = $this->actionExecutor->evaluateExpression(
                'is_consents_accepted',
                ['acceptedConsents' => $workflowItem->getData()->offsetGet('customerConsents')]
            );
            $workflowItem->getResult()->offsetSet('isConsentsAccepted', $isConsentsAccepted);
        }

        if ($isConsentsAccepted) {
            return;
        }

        // Show flash message only if we are returning to consent page.
        if ($workflowItem->getData()->offsetGet('consents_available')) {
            $this->actionExecutor->executeAction(
                'flash_message',
                [
                    'type' => 'warning',
                    'message' => 'oro.checkout.workflow.condition.required_consents_should_be_checked.message'
                ]
            );
        }

        $this->workflowManager->transitIfAllowed($workflowItem, self::TRANSITION_NAME);

        $currentStep = $workflowItem->getCurrentStep();
        if ($currentStep) {
            $event->setWorkflowStep($currentStep);
            $event->stopPropagation();
        }
    }

    /**
     * Set consents_available to true when consent page was shown for checkout workflow.
     */
    public function onStepEntered(StepEnteredEvent $event): void
    {
        $workflowItem = $event->getWorkflowItem();
        if ($workflowItem->getCurrentStep()?->getName() !== self::CUSTOMER_CONSENTS_STEP) {
            return;
        }

        if (!in_array(self::CHECKOUT_GROUP, (array)$workflowItem->getDefinition()?->getExclusiveRecordGroups(), true)) {
            return;
        }

        $workflowItem->getData()->offsetSet('consents_available', true);
    }

    private function isApplicable(WorkflowItem $workflowItem): bool
    {
        $currentStepName = $workflowItem->getCurrentStep()?->getName();
        // Cover all start transitions with consents check for logged-in users.
        if (!$currentStepName) {
            return !$this->isGuest();
        }

        $workflow = $this->workflowManager->getWorkflow($workflowItem);
        if (!$workflow) {
            return false;
        }

        $destinationStepName = $workflow->getTransitionManager()
            ->getTransition(self::TRANSITION_NAME)
            ?->getStepTo()
            ?->getName();

        // Do not execute transition if we are already at consents step
        if ($destinationStepName === $currentStepName) {
            return false;
        }

        // Do not execute transition if it is not allowed for the current step
        $step = $workflow->getStepManager()->getStep($currentStepName);
        if (!$step?->isAllowedTransition(self::TRANSITION_NAME)) {
            return false;
        }

        return true;
    }

    private function isGuest(): bool
    {
        $customerUser = $this->tokenStorage->getToken()?->getUser();
        if (!$customerUser instanceof CustomerUser) {
            return true;
        }

        return $customerUser->isGuest();
    }
}
