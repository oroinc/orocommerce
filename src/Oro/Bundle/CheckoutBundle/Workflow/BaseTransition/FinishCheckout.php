<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\BaseTransition;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\CheckoutActionsInterface;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\CustomerUserActionsInterface;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\TransitionServiceAbstract;

/**
 * Base implementation of checkout finish_checkout transition.
 */
class FinishCheckout extends TransitionServiceAbstract
{
    public function __construct(
        private CustomerUserActionsInterface $customerUserActions,
        private CheckoutActionsInterface $checkoutActions,
        private PaymentTransactionProvider $paymentTransactionProvider
    ) {
    }

    #[\Override]
    public function isConditionAllowed(WorkflowItem $workflowItem, ?Collection $errors = null): bool
    {
        /** @var Checkout $checkout */
        $checkout = $workflowItem->getEntity();

        $order = $checkout->getOrder();
        if (!$order) {
            return false;
        }

        if (!$checkout->isPaymentInProgress()) {
            return false;
        }

        $paymentTransaction = $this->paymentTransactionProvider->getPaymentTransaction($order);
        // a declined payment, the same as in DeclinedPaymentStatusCalculator; an active but not yet successful
        // transaction (e.g. a reservation or a payment that awaits a confirmation) does not block the checkout
        if (!$paymentTransaction || (!$paymentTransaction->isSuccessful() && !$paymentTransaction->isActive())) {
            return false;
        }

        return true;
    }

    #[\Override]
    public function execute(WorkflowItem $workflowItem): void
    {
        /** @var Checkout $checkout */
        $checkout = $workflowItem->getEntity();
        $checkout->setPaymentInProgress(false);
        $data = $workflowItem->getData();
        $order = $checkout->getOrder();

        $this->customerUserActions->handleLateRegistration($checkout, $order, $data->offsetGet('late_registration'));
        $this->checkoutActions->finishCheckout(
            $checkout,
            $order,
            (bool)$data->offsetGet('auto_remove_source'),
            (bool)$data->offsetGet('allow_manual_source_remove'),
            (bool)$data->offsetGet('remove_source'),
            (bool)$data->offsetGet('clear_source')
        );
    }
}
