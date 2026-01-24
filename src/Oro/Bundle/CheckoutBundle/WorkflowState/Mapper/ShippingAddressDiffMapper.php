<?php

namespace Oro\Bundle\CheckoutBundle\WorkflowState\Mapper;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;

/**
 * Maps shipping address changes for checkout state diff tracking.
 *
 * Extends the abstract address diff mapper to specifically track changes to the shipping address
 * in checkout workflow state.
 */
class ShippingAddressDiffMapper extends AbstractAddressDiffMapper
{
    const DATA_NAME = 'shipping_address';

    #[\Override]
    public function getName()
    {
        return self::DATA_NAME;
    }

    #[\Override]
    public function getAddress(Checkout $checkout)
    {
        return $checkout->getShippingAddress();
    }
}
