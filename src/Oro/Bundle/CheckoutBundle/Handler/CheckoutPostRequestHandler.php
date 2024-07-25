<?php

namespace Oro\Bundle\CheckoutBundle\Handler;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\Event\CheckoutTransitionAfterEvent;
use Oro\Bundle\CheckoutBundle\Event\CheckoutTransitionBeforeEvent;
use Oro\Bundle\CheckoutBundle\Layout\DataProvider\TransitionFormProvider;
use Oro\Bundle\CheckoutBundle\Layout\DataProvider\TransitionProvider;
use Oro\Bundle\CheckoutBundle\WorkflowState\Handler\CheckoutErrorHandler;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Handles checkout controller HTTP POST request.
 */
class CheckoutPostRequestHandler implements CheckoutHandlerInterface
{
    public function __construct(
        private WorkflowManager $workflowManager,
        private TransitionProvider $transitionProvider,
        private TransitionFormProvider $transitionFormProvider,
        private CheckoutErrorHandler $errorHandler,
        private EventDispatcherInterface $eventDispatcher
    ) {
    }

    public function isSupported(Request $request): bool
    {
        return $request->isMethod(Request::METHOD_POST);
    }

    public function handle(WorkflowItem $workflowItem, Request $request): void
    {
        $transition = $this->getContinueTransition($workflowItem, (string) $request->get('transition'));
        if (!$transition) {
            return;
        }

        $this->eventDispatcher->dispatch(
            new CheckoutTransitionBeforeEvent($workflowItem, $transition),
            'oro_checkout.transition_request.before'
        );

        $errors = new ArrayCollection();

        $transitionForm = $this->transitionFormProvider->getTransitionFormByTransition($workflowItem, $transition);
        if (!$transitionForm) {
            $isAllowed = false;
            if ($this->workflowManager->isTransitionAvailable($workflowItem, $transition, $errors)) {
                $this->workflowManager->transitUnconditionally($workflowItem, $transition);
                $isAllowed = true;
            }
        } else {
            $transitionForm->handleRequest($request);
            if ($transitionForm->isSubmitted() && $transitionForm->isValid()) {
                $this->workflowManager->transitUnconditionally($workflowItem, $transition);
                $isAllowed = true;
            } else {
                $this->errorHandler->addFlashWorkflowStateWarning($transitionForm->getErrors());
                $isAllowed = false;
            }

            $errors = new ArrayCollection($this->errorHandler->getWorkflowErrors($transitionForm->getErrors()));
        }

        $this->eventDispatcher->dispatch(
            new CheckoutTransitionAfterEvent($workflowItem, $transition, $isAllowed, $errors),
            'oro_checkout.transition_request.after'
        );

        $this->transitionProvider->clearCache();
    }

    private function getContinueTransition(WorkflowItem $workflowItem, string $transitionName): ?Transition
    {
        if ($transitionName) {
            $transition = $this->getTransition($workflowItem, $transitionName);
            if (!$transition || empty($transition->getFrontendOptions()['is_checkout_continue'])) {
                $transition = null;
            }
        } else {
            $continueTransitionData = $this->transitionProvider->getContinueTransition($workflowItem);
            if (!empty($continueTransitionData)) {
                $transition = $continueTransitionData->getTransition();
            }
        }

        return $transition ?? null;
    }

    private function getTransition(WorkflowItem $workflowItem, string $transitionName): ?Transition
    {
        $workflow = $this->workflowManager->getWorkflow($workflowItem);
        $transition = $workflow->getTransitionManager()->getTransition($transitionName);
        if (!$transition || !$workflow->checkTransitionValid($transition, $workflowItem, false)) {
            return null;
        }

        return $transition;
    }
}
