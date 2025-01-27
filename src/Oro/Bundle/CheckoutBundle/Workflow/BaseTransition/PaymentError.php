<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\BaseTransition;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\TransitionServiceAbstract;
use Oro\Bundle\WorkflowBundle\Model\TransitionServiceInterface;

/**
 * Base implementation of checkout payment_error transition.
 */
class PaymentError extends TransitionServiceAbstract
{
    public function __construct(
        private readonly TransitionServiceInterface $baseTransition,
        private readonly ManagerRegistry $doctrine
    ) {
    }

    #[\Override]
    public function execute(WorkflowItem $workflowItem): void
    {
        $this->baseTransition->execute($workflowItem);

        /** @var Checkout $checkout */
        $checkout = $workflowItem->getEntity();
        $checkout->setPaymentInProgress(false);
        $order = $checkout->getOrder();
        if (null !== $order) {
            $checkout->setOrder(null);
            $this->doctrine->getManagerForClass(Order::class)->remove($order);
        }
    }
}
