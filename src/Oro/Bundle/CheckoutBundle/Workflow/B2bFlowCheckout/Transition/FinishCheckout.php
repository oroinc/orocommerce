<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckout\Transition;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckout\ActionGroup\CheckoutActionsInterface;
use Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckout\ActionGroup\CustomerUserActionsInterface;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\TransitionServiceAbstract;

class FinishCheckout extends TransitionServiceAbstract
{
    public function __construct(
        private CustomerUserActionsInterface $customerUserActions,
        private CheckoutActionsInterface $checkoutActions
    ) {
    }

    public function isConditionAllowed(WorkflowItem $workflowItem, Collection $errors = null): bool
    {
        $data = $workflowItem->getData();

        if (!$data->offsetGet('order')) {
            return false;
        }

        if (!$data->offsetGet('payment_in_progress')) {
            return false;
        }

        return true;
    }

    public function execute(WorkflowItem $workflowItem): void
    {
        /** @var Checkout $checkout */
        $checkout = $workflowItem->getEntity();
        $data = $workflowItem->getData();
        /** @var Order $order */
        $order = $data->offsetGet('order');

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
