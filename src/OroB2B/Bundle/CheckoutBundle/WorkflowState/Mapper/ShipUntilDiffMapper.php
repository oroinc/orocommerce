<?php

namespace OroB2B\Bundle\CheckoutBundle\WorkflowState\Mapper;

use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;

class ShipUntilDiffMapper implements CheckoutStateDiffMapperInterface
{
    const DATA_NAME = 'shipUntil';

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
     * @return \DateTime
     */
    public function getCurrentState($checkout)
    {
        return $checkout->getShipUntil();
    }

    /**
     * {@inheritdoc}
     */
    public function isStatesEqual($entity, $state1, $state2)
    {
        return $state1 == $state2;
    }
}
