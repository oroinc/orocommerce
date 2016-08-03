<?php

namespace OroB2B\Bundle\CheckoutBundle\WorkflowState\Mapper;

use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;

class ShippingAddressDiffMapper implements CheckoutStateDiffMapperInterface
{
    const DATA_NAME = 'shippingAddress';

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
        if (!empty($checkout->getShippingAddress()) &&
            !empty($checkout->getShippingAddress()->getAccountUserAddress())
        ) {
            return [
                'id' => $checkout->getShippingAddress()->getAccountUserAddress()->getId(),
                'updated' => $checkout->getShippingAddress()->getAccountUserAddress()->getUpdated(),
            ];
        }

        if (!empty($checkout->getShippingAddress())) {
            return [
                'text' => (string) $checkout->getShippingAddress(),
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
        if (!isset($savedState[$this->getName()])) {
            return true;
        }

        if (isset($savedState[$this->getName()]['id']) &&
            isset($savedState[$this->getName()]['updated']) &&
            $savedState[$this->getName()]['updated'] instanceof \DateTimeInterface
        ) {
            return $savedState[$this->getName()]['id'] === $this->getCurrentState($checkout)['id'] &&
                $savedState[$this->getName()]['updated'] >= $this->getCurrentState($checkout)['updated'];
        } elseif (isset($savedState[$this->getName()]['text'])) {
            return $savedState[$this->getName()]['text'] === (string) $checkout->getShippingAddress();
        }

        return true;
    }
}
