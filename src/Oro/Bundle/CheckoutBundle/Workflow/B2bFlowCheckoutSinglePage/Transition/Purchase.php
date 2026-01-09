<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckoutSinglePage\Transition;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\CheckoutActionsInterface;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\PaymentMethodActionsInterface;
use Oro\Bundle\CheckoutBundle\Workflow\BaseTransition\ValidationTrait;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\TransitionServiceAbstract;
use Oro\Bundle\WorkflowBundle\Model\TransitionServiceInterface;
use Oro\Component\Action\Action\ExtendableAction;

/**
 * B2bFlowCheckoutSinglePage workflow transition purchase logic implementation.
 */
class Purchase extends TransitionServiceAbstract
{
    use ValidationTrait;

    public function __construct(
        private ActionExecutor $actionExecutor,
        private CheckoutActionsInterface $checkoutActions,
        private PaymentMethodActionsInterface $paymentMethodActions,
        private PaymentTransactionProvider $paymentTransactionProvider,
        private TransitionServiceInterface $baseContinueTransition
    ) {
    }

    #[\Override]
    public function isConditionAllowed(WorkflowItem $workflowItem, ?Collection $errors = null): bool
    {
        /** @var Checkout $checkout */
        $checkout = $workflowItem->getEntity();
        $data = $workflowItem->getData();

        if ($checkout->isCompleted()) {
            return false;
        }

        if (!$data->offsetGet('order')) {
            return false;
        }

        if (
            $this->paymentMethodActions->isPaymentMethodSupportsValidate($checkout)
            && !$this->paymentTransactionProvider->getActiveValidatePaymentTransaction($checkout->getPaymentMethod())
        ) {
            return false;
        }

        if (
            $this->isPurchaseViaDirectUrl()
            && !$this->isPurchaseViaDirectUrlAllowed($checkout, $workflowItem, $errors)
        ) {
            return false;
        }

        return true;
    }

    #[\Override]
    public function execute(WorkflowItem $workflowItem): void
    {
        /** @var Checkout $checkout */
        $checkout = $workflowItem->getEntity();
        $data = $workflowItem->getData();
        $workflowResult = $workflowItem->getResult();
        $order = $data->offsetGet('order');

        $data->offsetSet('payment_in_progress', true);

        $purchaseResult = $this->checkoutActions->purchase(
            $checkout,
            $order,
            [
                'additionalData' => $data->offsetGet('additional_data'),
                'email' => $data->offsetGet('email')
            ]
        );
        $responseData = array_merge((array)$workflowResult->offsetGet('responseData'), $purchaseResult['responseData']);
        $workflowResult->offsetSet('responseData', $responseData);

        $this->actionExecutor->executeAction(
            ExtendableAction::NAME,
            [
                'events' => ['extendable_action.finish_checkout'],
                'eventData' => [
                    'order' => $order,
                    'checkout' => $checkout,
                    'responseData' => $responseData,
                    'email' => $data->offsetGet('email')
                ]
            ]
        );

        if (!empty($responseData['purchaseSuccessful'])) {
            $workflowItem->setRedirectUrl($this->checkoutActions->getCheckoutUrl($checkout, 'finish_checkout'));
        } else {
            $workflowItem->setRedirectUrl($this->checkoutActions->getCheckoutUrl($checkout, 'payment_error'));
        }
    }

    private function isPurchaseViaDirectUrlAllowed(
        Checkout $checkout,
        WorkflowItem $workflowItem,
        ?Collection $errors = null
    ): bool {
        if (!$this->baseContinueTransition->isPreConditionAllowed($workflowItem, $errors)) {
            return false;
        }

        return $this->isValidationPassed($checkout, 'checkout_order_create_pre_checks', $errors);
    }

    private function isPurchaseViaDirectUrl(): bool
    {
        return $this->actionExecutor->evaluateExpression(
            'check_request',
            [
                'expected_key' => 'transition',
                'expected_value' => 'purchase'
            ]
        );
    }
}
