<?php

namespace Oro\Bundle\CheckoutBundle\AddressValidation\CheckoutHandler;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Helper\CheckoutWorkflowHelper;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\UpdateCheckoutStateInterface;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;

/**
 * Handles address validation for billing address on checkout.
 */
class BillingAddressValidationCheckoutHandler implements AddressValidationCheckoutHandlerInterface
{
    public function __construct(
        private ManagerRegistry $doctrine,
        private CheckoutWorkflowHelper $checkoutWorkflowHelper,
        private UpdateCheckoutStateInterface $updateCheckoutStateAction
    ) {
    }

    #[\Override]
    public function handle(
        Checkout $checkout,
        OrderAddress $selectedAddress,
        ?WorkflowData $submittedWorkflowData = null
    ): void {
        $entityManager = $this->doctrine->getManagerForClass(Checkout::class);

        $oldAddress = $checkout->getBillingAddress();
        if ($selectedAddress !== $oldAddress) {
            if ($oldAddress !== null) {
                $entityManager->remove($oldAddress);
            }

            $checkout->setBillingAddress($selectedAddress);
        }

        $workflowItem = $this->checkoutWorkflowHelper->getWorkflowItem($checkout);

        // Updates original WorkflowItem with the submitted to Address Validation to save the already submitted fields.
        if ($submittedWorkflowData !== null) {
            $workflowItem->getData()->add($submittedWorkflowData->toArray());

            // Explicitly updates "$updated" field to make doctrine notice that WorkflowItem is changed.
            $workflowItem->setUpdated();
        }

        $this->updateCheckoutState($workflowItem, $checkout);

        $entityManager->flush();
    }

    private function updateCheckoutState(WorkflowItem $workflowItem, Checkout $checkout): void
    {
        $workflowData = $workflowItem->getData();
        if (!$workflowData->get('state_token')) {
            return;
        }

        $updateData = $this->updateCheckoutStateAction
            ->execute($checkout, $workflowData->get('state_token'), true, true);

        $workflowItem->getResult()->offsetSet('updateCheckoutState', $updateData);
    }
}
