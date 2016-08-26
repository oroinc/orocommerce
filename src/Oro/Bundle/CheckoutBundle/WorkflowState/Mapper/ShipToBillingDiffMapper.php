<?php

namespace Oro\Bundle\CheckoutBundle\WorkflowState\Mapper;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;

class ShipToBillingDiffMapper implements CheckoutStateDiffMapperInterface
{
    const DATA_NAME = 'ship_to_billing_address';

    /**
     * {@inheritdoc}
     */
    public function isEntitySupported($entity)
    {
        return is_object($entity) && $entity instanceof Checkout;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::DATA_NAME;
    }

    /**
     * @param Checkout $checkout
     * @return bool
     */
    public function getCurrentState($checkout)
    {
        return $checkout->isShipToBillingAddress();
    }

    /** {@inheritdoc} */
    public function isStatesEqual($entity, $state1, $state2)
    {
        return $state1 === $state2;
    }
}
