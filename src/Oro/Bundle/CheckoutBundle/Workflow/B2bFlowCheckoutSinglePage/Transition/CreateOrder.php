<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckoutSinglePage\Transition;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutPaymentContextProvider;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\UpdateShippingPriceInterface;
use Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckout\ActionGroup\CheckoutActionsInterface;
use Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckout\ActionGroup\CustomerUserActionsInterface;
use Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckout\ActionGroup\OrderActionsInterface;
use Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckout\ActionGroup\PaymentMethodActionsInterface;
use Oro\Bundle\CheckoutBundle\Workflow\BaseTransition\PlaceOrder as BasePlaceOrder;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\TransitionServiceInterface;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

/**
 * B2bFlowCheckoutSinglePage workflow transition create_order logic implementation.
 */
class CreateOrder extends BasePlaceOrder
{
    public function __construct(
        ActionExecutor $actionExecutor,
        CheckoutPaymentContextProvider $paymentContextProvider,
        OrderActionsInterface $orderActions,
        CheckoutActionsInterface $checkoutActions,
        TransitionServiceInterface $baseContinueTransition,
        private UpdateShippingPriceInterface $updateShippingPrice,
        private PaymentMethodActionsInterface $paymentMethodActions,
        private CustomerUserActionsInterface $customerUserActions,
        private WorkflowManager $workflowManager
    ) {
        parent::__construct(
            $actionExecutor,
            $paymentContextProvider,
            $orderActions,
            $checkoutActions,
            $baseContinueTransition
        );
    }

    #[\Override]
    public function isPreConditionAllowed(WorkflowItem $workflowItem, Collection $errors = null): bool
    {
        /** @var Checkout $checkout */
        $checkout = $workflowItem->getEntity();
        $data = $workflowItem->getData();

        $this->showPaymentInProgressNotification($checkout, (bool)$data->offsetGet('payment_in_progress'));

        $workflowResult = $workflowItem->getResult();
        if (!$workflowResult->offsetGet('saveStateUrl')) {
            $workflowResult->offsetSet(
                'saveStateUrl',
                $this->checkoutActions->getCheckoutUrl($checkout, 'save_state')
            );
        }

        if (!$this->hasApplicablePaymentMethods($checkout)) {
            $errors?->add(['message' => 'oro.checkout.workflow.condition.payment_method_was_not_selected.message']);

            return false;
        }

        if (!$this->isShippingMethodHasEnabledRules($checkout)) {
            $errors?->add(['message' => 'oro.checkout.workflow.condition.shipping_method_is_not_available.message']);

            return false;
        }

        return parent::isPreConditionAllowed($workflowItem, $errors);
    }

    #[\Override]
    public function isConditionAllowed(WorkflowItem $workflowItem, Collection $errors = null): bool
    {
        /** @var Checkout $checkout */
        $checkout = $workflowItem->getEntity();
        $data = $workflowItem->getData();

        if (!$this->checkRequest('_wid', 'ajax_checkout')) {
            $errors?->add(['message' => 'oro.checkout.workflow.condition.invalid_request.message']);

            return false;
        }

        if (!$this->isConsentsAccepted($data->offsetGet('customerConsents'))) {
            $errors?->add([
                'message' => 'oro.checkout.workflow.condition.' .
                    'required_consents_should_be_checked_on_single_page_checkout.message'
            ]);

            return false;
        }

        if (!$this->isCheckoutAddressValid($checkout, $errors)) {
            return false;
        }

        if (!$checkout->getShippingMethod()) {
            $errors?->add(['message' => 'oro.checkout.workflow.condition.shipping_method_is_not_available.message']);

            return false;
        }

        if (!$this->isPaymentMethodApplicable($checkout)) {
            $errors?->add(['message' => 'oro.checkout.workflow.condition.payment_method_was_not_selected.message']);

            return false;
        }

        return parent::isConditionAllowed($workflowItem, $errors);
    }

    #[\Override]
    public function execute(WorkflowItem $workflowItem): void
    {
        /** @var Checkout $checkout */
        $checkout = $workflowItem->getEntity();
        $data = $workflowItem->getData();
        $workflowResult = $workflowItem->getResult();

        $this->actionExecutor->executeAction(
            'save_accepted_consents',
            ['acceptedConsents' => $data->offsetGet('customerConsents')]
        );

        $this->updateShippingPrice->execute($checkout);

        $order = $this->orderActions->placeOrder($checkout);
        $data->offsetSet('order', $order);

        $email = $data->offsetGet('email');
        $this->customerUserActions->updateGuestCustomerUser($checkout, $email, $checkout->getBillingAddress());

        $validatePayment = $data->offsetGet('payment_validate');
        if ($validatePayment) {
            $this->doValidatePayment($workflowItem);
        }

        if (!$validatePayment
            || !empty($workflowResult->offsetGet('responseData')['successful'])
            || !$this->paymentMethodActions->isPaymentMethodSupportsValidate($checkout)
        ) {
            $this->workflowManager->transitIfAllowed($workflowItem, 'purchase');
        }
    }

    #[\Override]
    protected function showPaymentInProgressNotification(Checkout $checkout, bool $paymentInProgress): void
    {
        if (!$this->checkRequest()) {
            return;
        }

        if ($this->checkRequest('transition', 'purchase', false)) {
            return;
        }

        parent::showPaymentInProgressNotification($checkout, $paymentInProgress);
    }

    private function hasApplicablePaymentMethods(Checkout $checkout): bool
    {
        $paymentContext = $this->paymentContextProvider->getContext($checkout);
        if (!$paymentContext) {
            return false;
        }

        return $this->actionExecutor->evaluateExpression(
            'has_applicable_payment_methods',
            [
                'context' => $paymentContext
            ]
        );
    }

    private function isShippingMethodHasEnabledRules(Checkout $checkout): bool
    {
        if (!$checkout->getShippingMethod()) {
            return false;
        }

        return $this->actionExecutor->evaluateExpression(
            'shipping_method_has_enabled_shipping_rules',
            [$checkout->getShippingMethod()]
        );
    }

    private function checkRequest(?string $key = null, ?string $value = null, bool $checkAjax = true): bool
    {
        $data = [];
        if ($checkAjax) {
            $data['is_ajax'] = true;
        }
        if ($key !== null) {
            $data['expected_key'] = $key;
        }
        if ($value !== null) {
            $data['expected_value'] = $value;
        }

        return $this->actionExecutor->evaluateExpression('check_request', $data);
    }

    private function isConsentsAccepted(?Collection $customerConsents = null): bool
    {
        return $this->actionExecutor->evaluateExpression(
            'is_consents_accepted',
            ['acceptedConsents' => $customerConsents]
        );
    }

    private function isCheckoutAddressValid(Checkout $checkout, ?Collection $errors): bool
    {
        return $this->actionExecutor->evaluateExpression(
            'validate_checkout_addresses',
            [$checkout],
            $errors
        );
    }

    private function doValidatePayment(WorkflowItem $workflowItem): void
    {
        /** @var Checkout $checkout */
        $checkout = $workflowItem->getEntity();
        $data = $workflowItem->getData();
        $workflowResult = $workflowItem->getResult();

        $validateResult = $this->paymentMethodActions->validate(
            checkout: $checkout,
            successUrl: $this->checkoutActions->getCheckoutUrl($checkout, 'purchase'),
            failureUrl: $this->checkoutActions->getCheckoutUrl($checkout, 'payment_error'),
            additionalData: $data->offsetGet('additional_data'),
            saveForLaterUse: (bool)$data->offsetGet('payment_save_for_later')
        );
        if ($validateResult) {
            $workflowResult->offsetSet('responseData', $validateResult);
        }

        if (empty($validateResult['successful'])
            && $this->paymentMethodActions->isPaymentMethodSupportsValidate($checkout)
        ) {
            $workflowResult->offsetSet('updateCheckoutState', true);
        }
    }
}
