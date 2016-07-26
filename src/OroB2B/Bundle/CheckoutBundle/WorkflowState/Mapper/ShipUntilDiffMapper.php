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
     * @param Checkout $checkout
     * @param array $savedState
     * @return bool
     */
    public function isStateActual($checkout, array $savedState)
    {
        if (!isset($savedState[$this->getName()]) || !($savedState[$this->getName()] instanceof \DateTimeInterface)) {
            return true;
        }

        return $savedState[$this->getName()] === $checkout->getShipUntil();
    }
}
