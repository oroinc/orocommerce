<?php

namespace OroB2B\Bundle\CheckoutBundle\WorkflowState\Mapper;

use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;

class PoNumberDiffMapper implements CheckoutStateDiffMapperInterface
{
    const DATA_NAME = 'poNumber';

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
     * @return string
     */
    public function getCurrentState($checkout)
    {
        return $checkout->getPoNumber();
    }

    /**
     * @param Checkout $checkout
     * @param array $savedState
     * @return bool
     */
    public function isStateActual($checkout, array $savedState)
    {
        if (!isset($savedState[$this->getName()]) || !is_string($savedState[$this->getName()])) {
            return true;
        }

        return $savedState[$this->getName()] === $checkout->getPoNumber();
    }
}
