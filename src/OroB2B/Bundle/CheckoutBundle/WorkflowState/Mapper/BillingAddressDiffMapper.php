<?php

namespace OroB2B\Bundle\CheckoutBundle\WorkflowState\Mapper;

use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;

class BillingAddressDiffMapper implements CheckoutStateDiffMapperInterface
{
    const DATA_NAME = 'billingAddress';

    /**
     * @return int
     */
    public function getPriority()
    {
        return 10;
    }

    /**
     * @param object $entity
     * @return boolean
     */
    public function isEntitySupported($entity)
    {
        return $entity instanceof Checkout;
    }

    /**
     * @param Checkout $checkout
     * @return array
     */
    public function getCurrentState($checkout)
    {
        return [
            self::DATA_NAME => [
                'id' => $checkout->getBillingAddress()->getId(),
                'updated' => $checkout->getBillingAddress()->getUpdated(),
            ],
        ];
    }

    /**
     * @param Checkout $checkout
     * @param array $savedState
     * @return bool
     */
    public function compareStates($checkout, array $savedState)
    {
        return
            isset($savedState[self::DATA_NAME]) &&
            isset($savedState[self::DATA_NAME]['id']) &&
            isset($savedState[self::DATA_NAME]['updated']) &&
            $savedState[self::DATA_NAME]['updated'] instanceof \DateTimeInterface &&
            $savedState[self::DATA_NAME]['id'] === $checkout->getBillingAddress()->getId() &&
            $savedState[self::DATA_NAME]['updated'] >= $checkout->getBillingAddress()->getUpdated();
    }
}
