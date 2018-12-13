<?php

namespace Oro\Bundle\CheckoutBundle\Helper;

use Oro\Bundle\ActionBundle\Exception\ForbiddenActionGroupException;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\ActionGroupRegistry;
use Oro\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutInterface;
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
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Exception\AlreadySubmittedException;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Use it to process checkout workflow
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
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

    /**
     * @param WorkflowManager $workflowManager
     * @param ActionGroupRegistry $actionGroupRegistry
     * @param TransitionProvider $transitionProvider
     * @param TransitionFormProvider $transitionFormProvider
     * @param CheckoutErrorHandler $errorHandler
     * @param CheckoutLineItemsManager $lineItemsManager
     * @param CustomerRegistrationHandler $registrationHandler
     * @param ForgotPasswordHandler $forgotPasswordHandler
     * @param EventDispatcherInterface $eventDispatcher
     * @param TranslatorInterface $translator
     */
    public function __construct(
        WorkflowManager $workflowManager,
        ActionGroupRegistry $actionGroupRegistry,
        TransitionProvider $transitionProvider,
        TransitionFormProvider $transitionFormProvider,
        CheckoutErrorHandler $errorHandler,
        CheckoutLineItemsManager $lineItemsManager,
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
        $workflowItem = $this->getWorkflowItem($checkout);
        $stopPropagation = $this->stopPropagation($workflowItem);
        if ($stopPropagation) {
            return $workflowItem->getCurrentStep();
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
     * @return mixed|WorkflowItem
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

        $this->eventDispatcher->dispatch(CheckoutValidateEvent::NAME, $event);

        return $event->isCheckoutRestartRequired();
    }

    /**
     * @param WorkflowItem $workflowItem
     * @param CheckoutInterface $checkout
     *
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

    /**
     * @param CheckoutInterface $checkout
     * @param Request $request
     */
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
     * @param WorkflowItem $workflowItem
     * @param Request $request
     *
     * @throws ForbiddenActionGroupException
     * @throws \Exception
     * @throws AlreadySubmittedException
     */
    protected function handlePostTransition(WorkflowItem $workflowItem, Request $request)
    {
        $continueTransition = $this->transitionProvider
            ->getContinueTransition($workflowItem, $request->get('transition'));
        if (!$continueTransition) {
            return;
        }

        $this->addTransitionErrors($continueTransition, $request);

        $transitionForm = $this->transitionFormProvider->getTransitionForm($workflowItem, $continueTransition);
        if (!$transitionForm) {
            $this->workflowManager->transitIfAllowed(
                $workflowItem,
                $continueTransition->getTransition()
            );
            return;
        }

        $transitionForm->handleRequest($request);
        if ($transitionForm->isSubmitted() && !$transitionForm->isValid()) {
            $this->errorHandler->addFlashWorkflowStateWarning($transitionForm->getErrors());
            return;
        }

        $this->workflowManager->transitIfAllowed(
            $workflowItem,
            $continueTransition->getTransition()
        );

        $this->transitionProvider->clearCache();
    }

    /**
     * @param WorkflowItem $workflowItem
     * @param Request $request
     */
    protected function handleGetTransition(WorkflowItem $workflowItem, Request $request)
    {
        if ($request->query->has('transition')) {
            $transition = $request->get('transition');
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

    /**
     * @param WorkflowItem $workflowItem
     * @param Request $request
     */
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

    /**
     * @param TransitionData $continueTransition
     * @param Request        $request
     */
    private function addTransitionErrors(TransitionData $continueTransition, Request $request)
    {
        $errors = $continueTransition->getErrors();
        foreach ($errors as $error) {
            $request->getSession()->getFlashBag()->add(
                'error',
                $this->translator->trans($error['message'], $error['parameters'])
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

    /**
     * @param WorkflowItem $workflowItem
     * @param Request $request
     */
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
     * @param WorkflowItem $workflowItem
     * @param Request $request
     *
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
