<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckoutSinglePage\Transition;

use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\CheckoutBundle\Action\DefaultShippingMethodSetterInterface;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\TransitionServiceAbstract;
use Oro\Bundle\WorkflowBundle\Model\TransitionServiceInterface;

/**
 * B2bFlowCheckoutSinglePage workflow transition save_state logic implementation.
 */
class SaveState extends TransitionServiceAbstract
{
    public function __construct(
        private ActionExecutor $actionExecutor,
        private DefaultShippingMethodSetterInterface $defaultShippingMethodSetter,
        private TransitionServiceInterface $baseTransition
    ) {
    }

    #[\Override]
    public function execute(WorkflowItem $workflowItem): void
    {
        $this->baseTransition->execute($workflowItem);

        /** @var Checkout $checkout */
        $checkout = $workflowItem->getEntity();
        $data = $workflowItem->getData();
        if (!$this->isConsentsAccepted($workflowItem)) {
            $data->offsetSet('consents_available', true);
        }

        if (null === $checkout->getShippingCost()) {
            $checkout->setShippingMethod(null);
            $this->defaultShippingMethodSetter->setDefaultShippingMethod($checkout);
        }

        $responseData = (array)$workflowItem->getResult()->offsetGet('responseData');
        $responseData['stateSaved'] = true;
        $workflowItem->getResult()->offsetSet('responseData', $responseData);
    }

    private function isConsentsAccepted(WorkflowItem $workflowItem): bool
    {
        return $this->actionExecutor->evaluateExpression(
            'is_consents_accepted',
            ['acceptedConsents' => $workflowItem->getData()->offsetGet('customerConsents')]
        );
    }
}
