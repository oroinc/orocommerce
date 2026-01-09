<?php

namespace Oro\Bundle\CheckoutBundle\WorkflowState\Mapper;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;

/**
 * Maps "ship until" date changes for checkout state diff tracking.
 *
 * Tracks changes to the "ship until" date in a checkout, enabling detection of
 * modifications to the shipping deadline during the checkout workflow.
 */
class ShipUntilDiffMapper implements CheckoutStateDiffMapperInterface
{
    public const DATA_NAME = 'ship_until';

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
     * @return \DateTime
     */
    #[\Override]
    public function getCurrentState($checkout)
    {
        return $checkout->getShipUntil();
    }

    #[\Override]
    public function isStatesEqual($entity, $state1, $state2)
    {
        return $state1 == $state2;
    }
}
