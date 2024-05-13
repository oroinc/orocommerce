<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckout\Transition;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\CheckoutBundle\Action\DefaultShippingMethodSetterInterface;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\ConfigProvider;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\TransitionServiceInterface;

class BackToShippingMethod extends ClearPaymentMethodAndRecalculateState
{
    public function __construct(
        private ConfigProvider $configProvider,
        private DefaultShippingMethodSetterInterface $defaultShippingMethodSetter,
        private TransitionServiceInterface $baseTransition
    ) {
    }

    public function isPreConditionAllowed(WorkflowItem $workflowItem, Collection $errors = null): bool
    {
        return $this->baseTransition->isPreConditionAllowed($workflowItem, $errors);
    }

    public function execute(WorkflowItem $workflowItem): void
    {
        $this->baseTransition->execute($workflowItem);

        /** @var Checkout $checkout */
        $checkout = $workflowItem->getEntity();

        if (!$this->configProvider->isMultiShippingEnabled()) {
            $this->defaultShippingMethodSetter->setDefaultShippingMethod($checkout);
        }
        $workflowItem->getData()->offsetSet('shipping_data_ready', false);
    }
}
