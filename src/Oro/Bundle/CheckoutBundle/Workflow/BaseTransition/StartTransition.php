<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\BaseTransition;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\TransitionServiceAbstract;

/**
 * Base implementation of checkout start transition.
 */
class StartTransition extends TransitionServiceAbstract
{
    #[\Override]
    public function execute(WorkflowItem $workflowItem): void
    {
        $data = $workflowItem->getData();
        $data->offsetSet('shipping_method', null);
        $data->offsetSet('payment_save_for_later', null);
    }
}
