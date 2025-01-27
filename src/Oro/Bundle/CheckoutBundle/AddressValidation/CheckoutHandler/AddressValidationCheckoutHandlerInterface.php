<?php

namespace Oro\Bundle\CheckoutBundle\AddressValidation\CheckoutHandler;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;

/**
 * Handles the selected address on address validation on checkout.
 */
interface AddressValidationCheckoutHandlerInterface
{
    public function handle(
        Checkout $checkout,
        OrderAddress $selectedAddress,
        ?WorkflowData $submittedWorkflowData = null
    ): void;
}
