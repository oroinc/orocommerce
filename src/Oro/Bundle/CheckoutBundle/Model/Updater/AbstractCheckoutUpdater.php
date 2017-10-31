<?php

namespace Oro\Bundle\CheckoutBundle\Model\Updater;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\WorkflowData\WorkflowDataUpdaterInterface;

abstract class AbstractCheckoutUpdater implements WorkflowDataUpdaterInterface
{
    /**
     * {@inheritDoc}
     */
    public function isApplicable(WorkflowDefinition $workflow, $source)
    {
        return in_array('b2b_checkout_flow', $workflow->getExclusiveRecordGroups(), true) && $source instanceof Order;
    }
}
