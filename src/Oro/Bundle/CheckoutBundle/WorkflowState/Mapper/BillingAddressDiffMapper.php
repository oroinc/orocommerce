<?php

namespace Oro\Bundle\CheckoutBundle\WorkflowState\Mapper;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;

/**
 * Maps billing address changes for checkout state diff tracking.
 *
 * Extends the abstract address diff mapper to specifically track changes to the billing address
 * in checkout workflow state.
 */
class BillingAddressDiffMapper extends AbstractAddressDiffMapper
{
    public const DATA_NAME = 'billing_address';

    #[\Override]
    public function getName()
    {
        return self::DATA_NAME;
    }

    #[\Override]
    public function getAddress(Checkout $checkout)
    {
        return $checkout->getBillingAddress();
    }
}
