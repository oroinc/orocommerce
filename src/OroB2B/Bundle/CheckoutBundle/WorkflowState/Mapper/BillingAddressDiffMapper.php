<?php

namespace OroB2B\Bundle\CheckoutBundle\WorkflowState\Mapper;

use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;

class BillingAddressDiffMapper implements CheckoutStateDiffMapperInterface
{
    const DATA_NAME = 'billingAddress';

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
     * @return array
     */
    public function getCurrentState($checkout)
    {
        if (!empty($checkout->getBillingAddress())) {
            return [
                'id' => $checkout->getBillingAddress()->getId(),
                'updated' => $checkout->getBillingAddress()->getUpdated(),
            ];
        }

        return [];
    }

    /**
     * @param Checkout $checkout
     * @param array $savedState
     * @return bool
     */
    public function isStateActual($checkout, array $savedState)
    {
        if (isset($savedState[$this->getName()]) &&
            empty($savedState[$this->getName()]) && empty($checkout->getBillingAddress())
        ) {
            return true;
        }

        // TODO: make proper address compare
        return
            isset($savedState[$this->getName()]) &&
            isset($savedState[$this->getName()]['id']) &&
            isset($savedState[$this->getName()]['updated']) &&
            $savedState[$this->getName()]['updated'] instanceof \DateTimeInterface &&
            true && //$savedState[$this->getName()]['id'] === $checkout->getBillingAddress()->getId() &&
            true; //$savedState[$this->getName()]['updated'] >= $checkout->getBillingAddress()->getUpdated();
    }
}
