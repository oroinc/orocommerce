<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckoutSinglePage\Transition;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\AddressActionsInterface;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\UpdateShippingPriceInterface;
use Oro\Bundle\OrderBundle\Manager\OrderAddressManager;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\TransitionServiceAbstract;

/**
 * Base implementation of state aware transition for B2bFlowCheckoutSinglePage workflow.
 */
class BaseStateAwareTransition extends TransitionServiceAbstract
{
    public function __construct(
        private AddressActionsInterface $addressActions,
        private OrderAddressManager $orderAddressManager,
        private UpdateShippingPriceInterface $updateShippingPrice
    ) {
    }

    public function execute(WorkflowItem $workflowItem): void
    {
        /** @var Checkout $checkout */
        $checkout = $workflowItem->getEntity();
        $data = $workflowItem->getData();

        $data->offsetSet(
            'billing_address_has_shipping',
            $data->offsetGet('ship_to_billing_address')
        );

        if (!$checkout->getBillingAddress()) {
            $customerUserAddresses = $this->orderAddressManager->getGroupedAddresses($checkout, 'billing');

            $billingAddress = $this->orderAddressManager->updateFromAbstract(
                $customerUserAddresses->getDefaultAddress()
            );
            $checkout->setBillingAddress($billingAddress);
        }

        if (!$checkout->getShippingAddress()) {
            $customerUserAddresses = $this->orderAddressManager->getGroupedAddresses($checkout, 'shipping');

            $shippingAddress = $this->orderAddressManager->updateFromAbstract(
                $customerUserAddresses->getDefaultAddress()
            );
            $checkout->setShippingAddress($shippingAddress);
        }

        $data->offsetSet(
            'billing_address_has_shipping',
            $this->addressActions->updateBillingAddress(
                $checkout,
                (bool)$data->offsetGet('disallow_shipping_address_edit')
            )
        );

        $this->addressActions->updateShippingAddress($checkout);
        $this->updateShippingPrice->execute($checkout);
    }
}
