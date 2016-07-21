<?php

namespace OroB2B\Bundle\CheckoutBundle\WorkflowState\Mapper;

use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;

class CustomerNotesDiffMapper implements CheckoutStateDiffMapperInterface
{
    const DATA_NAME = 'customerNotes';

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
        return $checkout->getCustomerNotes();
    }

    /**
     * @param Checkout $checkout
     * @param array $savedState
     * @return bool
     */
    public function isStateActual($checkout, array $savedState)
    {
        if (isset($savedState[$this->getName()]) &&
            empty($savedState[$this->getName()]) && empty($checkout->getCustomerNotes())
        ) {
            return true;
        }

        return
            isset($savedState[$this->getName()]) &&
            $savedState[$this->getName()] === $checkout->getCustomerNotes();
    }
}
