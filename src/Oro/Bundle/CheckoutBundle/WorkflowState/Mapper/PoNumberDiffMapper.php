<?php

namespace Oro\Bundle\CheckoutBundle\WorkflowState\Mapper;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;

/**
 * Maps PO number changes for checkout state diff tracking.
 *
 * Tracks changes to the purchase order number in a checkout, enabling detection of
 * modifications to the PO number during the checkout workflow.
 */
class PoNumberDiffMapper implements CheckoutStateDiffMapperInterface
{
    const DATA_NAME = 'po_number';

    #[\Override]
    public function isEntitySupported($entity)
    {
        return is_object($entity) && $entity instanceof Checkout;
    }

    #[\Override]
    public function getName()
    {
        return self::DATA_NAME;
    }

    /**
     * @param Checkout $checkout
     * @return string
     */
    #[\Override]
    public function getCurrentState($checkout)
    {
        return $checkout->getPoNumber();
    }

    #[\Override]
    public function isStatesEqual($entity, $state1, $state2)
    {
        return $state1 === $state2;
    }
}
