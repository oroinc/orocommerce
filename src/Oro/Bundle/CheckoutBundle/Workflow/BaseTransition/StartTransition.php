<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\BaseTransition;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\TransitionServiceAbstract;

class StartTransition extends TransitionServiceAbstract
{
    public function execute(WorkflowItem $workflowItem): void
    {
        $data = $workflowItem->getData();
        $data->offsetSet('shipping_method', null);
        $data->offsetSet('payment_save_for_later', null);
    }
}
