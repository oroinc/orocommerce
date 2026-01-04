<?php

namespace Oro\Bundle\CheckoutBundle\WorkflowState\Mapper;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;

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
