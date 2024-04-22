<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckout\Transition;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\CheckoutBundle\Action\DefaultPaymentMethodSetterInterface;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\AvailableShippingMethodCheckerInterface;
use Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckout\ActionGroup\ShippingMethodActionsInterface;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\TransitionServiceInterface;

/**
 * Implementation of continue_to_shipping_method transition logic of the checkout workflow.
 */
class ContinueToPayment implements TransitionServiceInterface
{
    public function __construct(
        private ShippingMethodActionsInterface $shippingMethodActions,
        private AvailableShippingMethodCheckerInterface $availableShippingMethodChecker,
        private DefaultPaymentMethodSetterInterface $defaultPaymentMethodSetter,
        private TransitionServiceInterface $baseContinueTransition
    ) {
    }

    public function isPreConditionAllowed(WorkflowItem $workflowItem, Collection $errors = null): bool
    {
        if (!$this->baseContinueTransition->isPreConditionAllowed($workflowItem, $errors)) {
            return false;
        }

        /** @var Checkout $checkout */
        $checkout = $workflowItem->getEntity();
        $data = $workflowItem->getData();
        $this->shippingMethodActions->updateDefaultShippingMethods(
            $checkout,
            $data->offsetGet('line_items_shipping_methods'),
            $data->offsetGet('line_item_groups_shipping_methods')
        );

        if (!$this->availableShippingMethodChecker->hasAvailableShippingMethods($checkout)) {
            $errors?->add(
                ['message' => 'oro.checkout.workflow.condition.shipping_method_is_not_available.message']
            );

            return false;
        }

        return true;
    }

    public function isConditionAllowed(WorkflowItem $workflowItem, Collection $errors = null): bool
    {
        /** @var Checkout $checkout */
        $checkout = $workflowItem->getEntity();
        if (!$checkout->getShippingMethod()) {
            $errors?->add(
                ['message' => 'oro.checkout.workflow.condition.shipping_method_is_not_available.message']
            );

            return false;
        }

        if (!$this->shippingMethodActions->hasApplicableShippingRules($checkout, $errors)['hasRules']) {
            return false;
        }

        return true;
    }

    public function execute(WorkflowItem $workflowItem): void
    {
        /** @var Checkout $checkout */
        $checkout = $workflowItem->getEntity();

        $this->shippingMethodActions->updateCheckoutShippingPrices($checkout);
        $this->defaultPaymentMethodSetter->setDefaultPaymentMethod($checkout);

        $workflowItem->getResult()->offsetSet('shippingPriceUpdated', true);
    }
}
