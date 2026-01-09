<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckoutSinglePage\Transition;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\CheckoutActionsInterface;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\CustomerUserActionsInterface;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\OrderActionsInterface;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\PaymentMethodActionsInterface;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\UpdateShippingPriceInterface;
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
        TransitionServiceInterface $baseContinueTransition,
        private OrderActionsInterface $orderActions,
        private CheckoutActionsInterface $checkoutActions,
        private UpdateShippingPriceInterface $updateShippingPrice,
        private PaymentMethodActionsInterface $paymentMethodActions,
        private CustomerUserActionsInterface $customerUserActions,
        private WorkflowManager $workflowManager
    ) {
        parent::__construct($actionExecutor, $baseContinueTransition);
    }

    #[\Override]
    public function isPreConditionAllowed(WorkflowItem $workflowItem, ?Collection $errors = null): bool
    {
        /** @var Checkout $checkout */
        $checkout = $workflowItem->getEntity();
        $data = $workflowItem->getData();

        $this->showPaymentInProgressNotification($checkout, (bool)$data->offsetGet('payment_in_progress'));
        $this->initializeSaveStateUrl($workflowItem);

        return parent::isPreConditionAllowed($workflowItem, $errors);
    }

    #[\Override]
    public function isConditionAllowed(WorkflowItem $workflowItem, ?Collection $errors = null): bool
    {
        if (!$this->checkRequest('_wid', 'ajax_checkout')) {
            $errors?->add(['message' => 'oro.checkout.workflow.condition.invalid_request.message']);

            return false;
        }

        $data = $workflowItem->getData();
        if (!$this->isConsentsAccepted($data->offsetGet('customerConsents'))) {
            $errors?->add([
                'message' => 'oro.checkout.workflow.condition.' .
                    'required_consents_should_be_checked_on_single_page_checkout.message'
            ]);

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
        $this->orderActions->placeOrder($checkout);

        $email = $data->offsetGet('email');
        $this->customerUserActions->updateGuestCustomerUser($checkout, $email, $checkout->getBillingAddress());

        $validatePayment = $data->offsetGet('payment_validate');
        if ($validatePayment) {
            $this->doValidatePayment($workflowItem);
        }

        if (
            !$validatePayment
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

    private function doValidatePayment(WorkflowItem $workflowItem): void
    {
        /** @var Checkout $checkout */
        $checkout = $workflowItem->getEntity();
        $data = $workflowItem->getData();
        $workflowResult = $workflowItem->getResult();

        $validateResult = $this->paymentMethodActions->validate(
            $checkout,
            $this->checkoutActions->getCheckoutUrl($checkout, 'purchase'),
            $this->checkoutActions->getCheckoutUrl($checkout, 'payment_error'),
            $data->offsetGet('additional_data'),
            (bool)$data->offsetGet('payment_save_for_later')
        );
        if ($validateResult) {
            $workflowResult->offsetSet('responseData', $validateResult);
        }

        if (
            empty($validateResult['successful'])
            && $this->paymentMethodActions->isPaymentMethodSupportsValidate($checkout)
        ) {
            $workflowResult->offsetSet('updateCheckoutState', true);
        }
    }

    private function initializeSaveStateUrl(WorkflowItem $workflowItem): void
    {
        $workflowResult = $workflowItem->getResult();
        if (!$workflowResult->offsetGet('saveStateUrl')) {
            $workflowResult->offsetSet(
                'saveStateUrl',
                $this->checkoutActions->getCheckoutUrl($workflowItem->getEntity(), 'save_state')
            );
        }
    }
}
