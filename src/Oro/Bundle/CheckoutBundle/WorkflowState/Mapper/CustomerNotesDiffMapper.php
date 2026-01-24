<?php

namespace Oro\Bundle\CheckoutBundle\WorkflowState\Mapper;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;

/**
 * Maps customer notes changes for checkout state diff tracking.
 *
 * Tracks changes to customer notes in a checkout, enabling detection of modifications
 * to customer-provided notes during the checkout workflow.
 */
class CustomerNotesDiffMapper implements CheckoutStateDiffMapperInterface
{
    const DATA_NAME = 'customer_notes';

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
        return $checkout->getCustomerNotes();
    }

    #[\Override]
    public function isStatesEqual($entity, $state1, $state2)
    {
        return $state1 === $state2;
    }
}
