<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckoutSinglePage\Transition;

use Oro\Bundle\CheckoutBundle\Action\DefaultPaymentMethodSetterInterface;
use Oro\Bundle\CheckoutBundle\Action\DefaultShippingMethodSetterInterface;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckout\ActionGroup\CustomerUserActionsInterface;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\TransitionServiceAbstract;
use Oro\Bundle\WorkflowBundle\Model\TransitionServiceInterface;

class Start extends TransitionServiceAbstract
{
    public function __construct(
        private CustomerUserActionsInterface $customerUserActions,
        private DefaultShippingMethodSetterInterface $defaultShippingMethodSetter,
        private DefaultPaymentMethodSetterInterface $defaultPaymentMethodSetter,
        private TransitionServiceInterface $baseTransition
    ) {
    }

    public function execute(WorkflowItem $workflowItem): void
    {
        /** @var Checkout $checkout */
        $checkout = $workflowItem->getEntity();

        $this->baseTransition->execute($workflowItem);

        $this->customerUserActions->createGuestCustomerUser($checkout);
        $this->defaultShippingMethodSetter->setDefaultShippingMethod($checkout);
        $this->defaultPaymentMethodSetter->setDefaultPaymentMethod($checkout);
    }
}
