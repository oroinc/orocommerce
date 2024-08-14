<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\BaseTransition;

use Doctrine\Persistence\ManagerRegistry;
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
        private ManagerRegistry $registry,
        private TransitionServiceInterface $baseTrnasition
    ) {
    }

    public function execute(WorkflowItem $workflowItem): void
    {
        $this->baseTrnasition->execute($workflowItem);

        $data = $workflowItem->getData();
        $order = $data->offsetGet('order');
        if ($order) {
            $this->registry->getManagerForClass(Order::class)?->remove($order);
        }
        $data->offsetUnset('order');
    }
}
