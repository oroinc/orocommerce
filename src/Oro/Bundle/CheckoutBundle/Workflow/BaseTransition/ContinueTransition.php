<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\BaseTransition;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\TransitionServiceAbstract;

/**
 * Base implementation of B2bCheckout workflow continue transition (transition that leads to the next step).
 */
class ContinueTransition extends TransitionServiceAbstract
{
    use ValidationTrait;

    #[\Override]
    public function isPreConditionAllowed(WorkflowItem $workflowItem, Collection $errors = null): bool
    {
        /** @var Checkout $checkout */
        $checkout = $workflowItem->getEntity();

        if ($checkout->isCompleted()) {
            return false;
        }

        return $this->isValidationPassed($checkout, 'checkout_pre_checks', $errors);
    }
}
