<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckout\Operation;

use Oro\Bundle\ActionBundle\Model\AbstractOperationService;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\CheckoutBundle\Action\DefaultShippingMethodSetterInterface;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\AddressActionsInterface;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\UpdateShippingPriceInterface;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowException;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

/**
 * b2b_flow_checkout_new_billing_address operation logic implementation
 */
class NewBillingAddress extends AbstractOperationService
{
    public function __construct(
        private WorkflowManager $workflowManager,
        private ActionExecutor $actionExecutor,
        private AddressActionsInterface $addressActions,
        private UpdateShippingPriceInterface $updateShippingPrice,
        private DefaultShippingMethodSetterInterface $defaultShippingMethodSetter
    ) {
    }

    public function execute(ActionData $data): void
    {
        $checkout = $data->getEntity();
        if (!$checkout instanceof Checkout) {
            throw new WorkflowException('Only Checkout entity is supported');
        }

        $workflowItem = $this->workflowManager->getFirstWorkflowItemByEntity($checkout);
        if (!$workflowItem) {
            throw new WorkflowException('Could not find workflow item');
        }

        $this->actualizeEmail($workflowItem, $data);

        $checkout->setSaveBillingAddress((bool)$data->offsetGet('save_address'));
        $checkout->setShipToBillingAddress((bool)$data->offsetGet('ship_to_billing_address'));
        $this->addressActions->updateShippingAddress($checkout);

        $this->updateShippingPrice->execute($checkout);
        if ($checkout->getShippingCost() === null) {
            $checkout->setShippingMethod(null);
            $this->defaultShippingMethodSetter->setDefaultShippingMethod($checkout);
        }

        if ($data->offsetGet('oldAddress') instanceof OrderAddress &&
            $checkout->getBillingAddress()?->getId() !== $data->offsetGet('oldAddress')->getId()) {
            $this->actionExecutor->executeAction(
                'remove_entity',
                [$data->offsetGet('oldAddress')]
            );
        }

        $this->actionExecutor->executeActionGroup(
            'update_checkout_state',
            [
                'checkout' => $checkout,
                'state_token' => $workflowItem->getData()->get('state_token'),
                'update_checkout_state' => true,
            ]
        );

        $this->actionExecutor->executeAction(
            'flush_entity',
            [$checkout->getBillingAddress()]
        );
        $this->actionExecutor->executeAction(
            'flush_entity',
            [$checkout]
        );
    }

    private function actualizeEmail(WorkflowItem $workflowItem, ActionData $data): void
    {
        $workflowItem->getData()->offsetSet('email', $data->offsetGet('visitor_email'));
        $workflowItem->setUpdated();

        $this->actionExecutor->executeAction(
            'flush_entity',
            [$workflowItem]
        );
    }
}
