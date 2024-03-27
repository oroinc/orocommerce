<?php

namespace Oro\Bundle\CheckoutBundle\Helper;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutInterface;
use Oro\Bundle\CheckoutBundle\Event\CheckoutTransitionAfterEvent;
use Oro\Bundle\CheckoutBundle\Event\CheckoutTransitionBeforeEvent;
use Oro\Bundle\CheckoutBundle\Layout\DataProvider\TransitionFormProvider;
use Oro\Bundle\CheckoutBundle\Layout\DataProvider\TransitionProvider;
use Oro\Bundle\CheckoutBundle\Model\TransitionData;
use Oro\Bundle\CheckoutBundle\WorkflowState\Handler\CheckoutErrorHandler;
use Oro\Bundle\CustomerBundle\Handler\CustomerRegistrationHandler;
use Oro\Bundle\CustomerBundle\Handler\ForgotPasswordHandler;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Use it to process checkout workflow
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class CheckoutWorkflowHelper
{
    private EventDispatcherInterface $eventDispatcher;
    private WorkflowManager $workflowManager;
    private TransitionProvider $transitionProvider;
    private TransitionFormProvider $transitionFormProvider;
    private CheckoutLineItemsManager $lineItemsManager;
    private CheckoutLineItemGroupingInvalidationHelper $checkoutLineItemGroupingInvalidationHelper;
    private CustomerRegistrationHandler $registrationHandler;
    private ForgotPasswordHandler $forgotPasswordHandler;
    private CheckoutErrorHandler $errorHandler;
    private TranslatorInterface $translator;
    private array $workflowItems = [];

    public function __construct(
        WorkflowManager $workflowManager,
        TransitionProvider $transitionProvider,
        TransitionFormProvider $transitionFormProvider,
        CheckoutErrorHandler $errorHandler,
        CheckoutLineItemsManager $lineItemsManager,
        CheckoutLineItemGroupingInvalidationHelper $checkoutLineItemGroupingInvalidationHelper,
        CustomerRegistrationHandler $registrationHandler,
        ForgotPasswordHandler $forgotPasswordHandler,
        EventDispatcherInterface $eventDispatcher,
        TranslatorInterface $translator
    ) {
        $this->workflowManager = $workflowManager;
        $this->transitionProvider = $transitionProvider;
        $this->transitionFormProvider = $transitionFormProvider;
        $this->errorHandler = $errorHandler;
        $this->lineItemsManager = $lineItemsManager;
        $this->checkoutLineItemGroupingInvalidationHelper = $checkoutLineItemGroupingInvalidationHelper;
        $this->registrationHandler = $registrationHandler;
        $this->forgotPasswordHandler = $forgotPasswordHandler;
        $this->eventDispatcher = $eventDispatcher;
        $this->translator = $translator;
    }

    public function processWorkflowAndGetCurrentStep(Request $request, Checkout $checkout): WorkflowStep
    {
        $workflowItem = $this->getWorkflowItem($checkout);

        if ($this->checkoutLineItemGroupingInvalidationHelper->shouldInvalidateLineItemGrouping($workflowItem)) {
            $this->checkoutLineItemGroupingInvalidationHelper->invalidateLineItemGrouping($checkout, $workflowItem);
        }

        $this->processHandlers($workflowItem, $request);

        $currentStep = $this->validateStep($workflowItem);
        if ($this->isValidationNeeded($checkout, $workflowItem, $request)) {
            $this->checkLineItemsCount($checkout, $request);
        }

        return $currentStep;
    }

    public function getWorkflowItem(CheckoutInterface $checkout): WorkflowItem
    {
        $items = $this->findWorkflowItems($checkout);
        if (\count($items) !== 1) {
            throw new NotFoundHttpException('Unable to find correct WorkflowItem for current checkout');
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

    protected function checkLineItemsCount(Checkout $checkout, Request $request): void
    {
        $allOrderLineItemsCount = $this->lineItemsManager->getData($checkout, true)->count();

        if ($allOrderLineItemsCount) {
            $orderLineItemsCount = $this->lineItemsManager->getData($checkout)->count();
            if ($allOrderLineItemsCount !== $orderLineItemsCount) {
                $rfpOrderLineItems = $this->lineItemsManager
                    ->getData($checkout, true, 'oro_rfp.frontend_product_visibility');
                $message = $rfpOrderLineItems->isEmpty()
                    ? 'oro.checkout.order.line_items.line_item_has_no_price_not_allow_rfp.message'
                    : 'oro.checkout.order.line_items.line_item_has_no_price_allow_rfp.message';
                $request->getSession()->getFlashBag()->add('warning', $message);

                return;
            }
        }

        if ($allOrderLineItemsCount !== $checkout->getLineItems()?->count()) {
            $request->getSession()->getFlashBag()->add(
                'warning',
                'oro.checkout.order.line_items.line_item_has_no_price_not_allow_rfp.message'
            );
        }
    }

    protected function handlePostTransition(WorkflowItem $workflowItem, Request $request): void
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

    private function getTransition(WorkflowItem $workflowItem, string $transitionName): ?Transition
    {
        $workflow = $this->workflowManager->getWorkflow($workflowItem);
        $transition = $workflow->getTransitionManager()->getTransition($transitionName);
        if (!$transition || !$workflow->checkTransitionValid($transition, $workflowItem, false)) {
            return null;
        }

        return $transition;
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

    protected function handleGetTransition(WorkflowItem $workflowItem, Request $request): void
    {
        if ($request->query->has('transition')) {
            $transition = $request->get('transition');
            if ($transition === 'payment_error' && $request->query->has('layout_block_ids')) {
                // Do not transit workflow if requested only layout updates
                return;
            }

            $this->workflowManager->transitIfAllowed($workflowItem, $transition);
        }
    }

    protected function validateStep(WorkflowItem $workflowItem): WorkflowStep
    {
        $verifyTransition = null;
        $transitions = $this->workflowManager->getTransitionsByWorkflowItem($workflowItem);
        foreach ($transitions as $transition) {
            $frontendOptions = $transition->getFrontendOptions();
            if (!empty($frontendOptions['is_checkout_verify'])) {
                $verifyTransition = $transition;
                break;
            }
        }

        if ($verifyTransition) {
            $this->workflowManager->transitIfAllowed($workflowItem, $verifyTransition);
        }

        return $workflowItem->getCurrentStep();
    }

    protected function handleRegistration(WorkflowItem $workflowItem, Request $request): void
    {
        if ($request->isMethod(Request::METHOD_POST)) {
            $this->registrationHandler->handleRegistration($request);
            $form = $this->registrationHandler->getForm();
            if ($form->isSubmitted() && $form->isValid()) {
                $this->handleGetTransition($workflowItem, $request);
            }
        }
    }

    private function addTransitionErrors(TransitionData $continueTransition, Request $request): void
    {
        $errors = $continueTransition->getErrors();
        foreach ($errors as $error) {
            $request->getSession()->getFlashBag()->add(
                'error',
                $this->translator->trans((string) ($error['message'] ?? ''), (array) ($error['parameters'] ?? []))
            );
        }
    }

    private function isValidationNeeded(Checkout $checkout, WorkflowItem $workflowItem, Request $request): bool
    {
        if (!$checkout->getId()) {
            return false;
        }

        if ($request->isXmlHttpRequest()) {
            return false;
        }

        $continueTransition = $this->transitionProvider->getContinueTransition($workflowItem);
        if (!$continueTransition) {
            return false;
        }

        $frontendOptions = $continueTransition->getTransition()->getFrontendOptions();
        if (!\array_key_exists('is_checkout_show_errors', $frontendOptions)) {
            return false;
        }

        $this->addTransitionErrors($continueTransition, $request);

        $errors = $continueTransition->getErrors();
        if (!$errors->isEmpty()) {
            return false;
        }

        return true;
    }

    private function processHandlers(WorkflowItem $workflowItem, Request $request): void
    {
        if ($this->registrationHandler->isRegistrationRequest($request)) {
            $this->handleRegistration($workflowItem, $request);
        } elseif ($this->forgotPasswordHandler->isForgotPasswordRequest($request)) {
            $this->forgotPasswordHandler->handle($request);
        } else {
            $this->handleTransition($workflowItem, $request);
        }
    }

    private function handleTransition(WorkflowItem $workflowItem, Request $request): void
    {
        if ($request->isMethod(Request::METHOD_GET)) {
            $this->handleGetTransition($workflowItem, $request);
        } elseif ($request->isMethod(Request::METHOD_POST)) {
            $this->handlePostTransition($workflowItem, $request);
        }
    }
}
