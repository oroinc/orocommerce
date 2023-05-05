<?php

namespace Oro\Bundle\CheckoutBundle\Helper;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ActionBundle\Exception\ForbiddenActionGroupException;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\ActionGroupRegistry;
use Oro\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutInterface;
use Oro\Bundle\CheckoutBundle\Event\CheckoutTransitionAfterEvent;
use Oro\Bundle\CheckoutBundle\Event\CheckoutTransitionBeforeEvent;
use Oro\Bundle\CheckoutBundle\Event\CheckoutValidateEvent;
use Oro\Bundle\CheckoutBundle\Layout\DataProvider\TransitionFormProvider;
use Oro\Bundle\CheckoutBundle\Layout\DataProvider\TransitionProvider;
use Oro\Bundle\CheckoutBundle\Model\TransitionData;
use Oro\Bundle\CheckoutBundle\WorkflowState\Handler\CheckoutErrorHandler;
use Oro\Bundle\CustomerBundle\Handler\CustomerRegistrationHandler;
use Oro\Bundle\CustomerBundle\Handler\ForgotPasswordHandler;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowException;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Exception\AlreadySubmittedException;
use Symfony\Component\Form\FormInterface;
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
    /** @var EventDispatcherInterface  */
    private $eventDispatcher;

    /** @var WorkflowManager  */
    private $workflowManager;

    /** @var TransitionProvider  */
    private $transitionProvider;

    /** @var TransitionFormProvider  */
    private $transitionFormProvider;

    /** @var CheckoutLineItemsManager  */
    private $lineItemsManager;

    /** @var CheckoutLineItemGroupingInvalidationHelper */
    private $checkoutLineItemGroupingInvalidationHelper;

    /** @var CustomerRegistrationHandler  */
    private $registrationHandler;

    /** @var ActionGroupRegistry */
    private $actionGroupRegistry;

    /** @var ForgotPasswordHandler  */
    private $forgotPasswordHandler;

    /** @var CheckoutErrorHandler  */
    private $errorHandler;

    /** @var TranslatorInterface  */
    private $translator;

    public function __construct(
        WorkflowManager $workflowManager,
        ActionGroupRegistry $actionGroupRegistry,
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
        $this->actionGroupRegistry = $actionGroupRegistry;
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

    /**
     * @param Request  $request
     * @param Checkout $checkout
     *
     * @return WorkflowStep
     * @throws WorkflowException
     */
    public function processWorkflowAndGetCurrentStep(Request $request, Checkout $checkout)
    {
        $this->actionGroupRegistry->findByName('actualize_currency')
            ->execute(new ActionData(['checkout' => $checkout]));

        $workflowItem = $this->getWorkflowItem($checkout);
        $stopPropagation = $this->stopPropagation($workflowItem);
        if ($stopPropagation) {
            return $workflowItem->getCurrentStep();
        }

        if ($this->checkoutLineItemGroupingInvalidationHelper->shouldInvalidateLineItemGrouping($workflowItem)) {
            $this->checkoutLineItemGroupingInvalidationHelper->invalidateLineItemGrouping($checkout, $workflowItem);
        }

        if ($request->isMethod(Request::METHOD_POST) &&
            $this->isCheckoutRestartRequired($workflowItem)
        ) {
            $this->restartCheckout($workflowItem, $checkout);
            $workflowItem = $this->getWorkflowItem($checkout);
        } else {
            $this->processHandlers($workflowItem, $request);
        }

        $currentStep = $this->validateStep($workflowItem);
        if ($this->isValidationNeeded($checkout, $workflowItem, $request)) {
            $this->validateOrderLineItems($checkout, $request);
        }

        return $currentStep;
    }

    /**
     * @param CheckoutInterface $checkout
     * @return WorkflowItem
     *
     * @throws WorkflowException
     */
    public function getWorkflowItem(CheckoutInterface $checkout)
    {
        $items = $this->workflowManager->getWorkflowItemsByEntity($checkout);

        if (count($items) !== 1) {
            throw new NotFoundHttpException('Unable to find correct WorkflowItem for current checkout');
        }

        return reset($items);
    }

    /**
     * @param WorkflowItem $workflowItem
     *
     * @return bool
     */
    protected function isCheckoutRestartRequired(WorkflowItem $workflowItem)
    {
        $event = new CheckoutValidateEvent($workflowItem);
        if (false == $this->eventDispatcher->hasListeners(CheckoutValidateEvent::NAME)) {
            return false;
        }

        $this->eventDispatcher->dispatch($event, CheckoutValidateEvent::NAME);

        return $event->isCheckoutRestartRequired();
    }

    /**
     * @throws ForbiddenActionGroupException
     * @throws \Exception
     */
    protected function restartCheckout(WorkflowItem $workflowItem, CheckoutInterface $checkout)
    {
        $workflowName = $workflowItem->getWorkflowName();

        $shoppingList = $workflowItem->getEntity()->getSource()->getShoppingList();
        $this->workflowManager->resetWorkflowItem($workflowItem);
        $this->workflowManager->startWorkflow($workflowName, $checkout);

        $actionData = new ActionData(['shoppingList' => $shoppingList, 'forceStartCheckout' => true]);
        $this->actionGroupRegistry->findByName('start_shoppinglist_checkout')->execute($actionData);
    }

    protected function validateOrderLineItems(CheckoutInterface $checkout, Request $request)
    {
        $orderLineItemsCount = $this->lineItemsManager->getData($checkout, true)->count();

        if ($this->lineItemsManager->getLineItemsWithoutQuantity($checkout)->count()) {
            $request->getSession()->getFlashBag()->add(
                'warning',
                'oro.checkout.order.line_items.line_item_has_no_price_not_allow_rfp.message'
            );

            return;
        }

        if ($orderLineItemsCount && $orderLineItemsCount !== $this->lineItemsManager->getData($checkout)->count()) {
            $orderLineItemsRfp = $this->lineItemsManager
                ->getData($checkout, true, 'oro_rfp.frontend_product_visibility');
            $message = $orderLineItemsRfp->isEmpty()
                ? 'oro.checkout.order.line_items.line_item_has_no_price_not_allow_rfp.message'
                : 'oro.checkout.order.line_items.line_item_has_no_price_allow_rfp.message';
            $request->getSession()->getFlashBag()->add('warning', $message);
        }
    }

    /**
     * @throws ForbiddenActionGroupException
     * @throws \Exception
     * @throws AlreadySubmittedException
     */
    protected function handlePostTransition(WorkflowItem $workflowItem, Request $request)
    {
        $transition = $this->getContinueTransition($workflowItem, (string) $request->get('transition'));
        if (!$transition) {
            return;
        }

        $this->eventDispatcher->dispatch(new CheckoutTransitionBeforeEvent($workflowItem, $transition));

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
            new CheckoutTransitionAfterEvent(
                $workflowItem,
                $transition,
                $isAllowed,
                $errors
            )
        );

        $this->transitionProvider->clearCache();
    }

    /**
     * @throws WorkflowException
     */
    private function getTransition(WorkflowItem $workflowItem, string $transitionName): ?Transition
    {
        $workflow = $this->workflowManager->getWorkflow($workflowItem);
        $transition = $workflow->getTransitionManager()->getTransition($transitionName);
        if (!$transition || !$workflow->checkTransitionValid($transition, $workflowItem, false)) {
            return null;
        }

        return $transition;
    }

    /**
     * @throws WorkflowException
     */
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

    protected function handleGetTransition(WorkflowItem $workflowItem, Request $request)
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

    /**
     * @param WorkflowItem $workflowItem
     *
     * @return WorkflowStep
     */
    protected function validateStep(WorkflowItem $workflowItem)
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

    protected function handleRegistration(WorkflowItem $workflowItem, Request $request)
    {
        if ($request->isMethod(Request::METHOD_POST)) {
            $this->registrationHandler->handleRegistration($request);
            /** @var FormInterface $form */
            $form = $this->registrationHandler->getForm();

            if ($form->isSubmitted() && $form->isValid()) {
                $this->handleGetTransition($workflowItem, $request);
            }
        }
    }

    /**
     * @param WorkflowItem $workflowItem
     *
     * @return bool
     */
    private function stopPropagation(WorkflowItem $workflowItem)
    {
        $stopPropagation = false;
        $transitions = $this->workflowManager->getTransitionsByWorkflowItem($workflowItem);
        foreach ($transitions as $transition) {
            $frontendOptions = $transition->getFrontendOptions();
            if (!empty($frontendOptions['stop_propagation'])) {
                $transitionAllowed = $this->workflowManager->transitIfAllowed($workflowItem, $transition);
                if ($transitionAllowed) {
                    $stopPropagation = true;
                    break;
                }
            }
        }

        return $stopPropagation;
    }

    private function addTransitionErrors(TransitionData $continueTransition, Request $request)
    {
        $errors = $continueTransition->getErrors();
        foreach ($errors as $error) {
            $request->getSession()->getFlashBag()->add(
                'error',
                $this->translator->trans((string) ($error['message'] ?? ''), (array) ($error['parameters'] ?? []))
            );
        }
    }

    /**
     * @param Checkout $checkout
     * @param WorkflowItem $workflowItem
     * @param Request $request
     *
     * @return bool
     */
    private function isValidationNeeded(Checkout $checkout, WorkflowItem $workflowItem, Request $request)
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
        if (!array_key_exists('is_checkout_show_errors', $frontendOptions)) {
            return false;
        }

        $this->addTransitionErrors($continueTransition, $request);

        $errors = $continueTransition->getErrors();
        if (!$errors->isEmpty()) {
            return false;
        }

        return true;
    }

    private function processHandlers(WorkflowItem $workflowItem, Request $request)
    {
        if ($this->registrationHandler->isRegistrationRequest($request)) {
            $this->handleRegistration($workflowItem, $request);
        } elseif ($this->forgotPasswordHandler->isForgotPasswordRequest($request)) {
            $this->forgotPasswordHandler->handle($request);
        } else {
            $this->handleTransition($workflowItem, $request);
        }
    }

    /**
     * @throws ForbiddenActionGroupException
     * @throws \Exception
     * @throws AlreadySubmittedException
     */
    private function handleTransition(WorkflowItem $workflowItem, Request $request)
    {
        if ($request->isMethod(Request::METHOD_GET)) {
            $this->handleGetTransition($workflowItem, $request);
        } elseif ($request->isMethod(Request::METHOD_POST)) {
            $this->handlePostTransition($workflowItem, $request);
        }
    }
}
