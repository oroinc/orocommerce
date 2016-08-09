<?php

namespace OroB2B\Bundle\CheckoutBundle\WorkflowState\Mapper;

use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;

class ShipToBillingDiffMapper implements CheckoutStateDiffMapperInterface
{
    use IsStateEqualTrait;

    const DATA_NAME = 'shipToBillingAddress';

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
}
