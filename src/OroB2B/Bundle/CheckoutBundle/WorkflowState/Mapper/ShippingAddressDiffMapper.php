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
                'text' => $checkout->getShippingAddress()->__toString(),
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
            return $savedState[$this->getName()]['id'] ===
                    $checkout->getShippingAddress()->getAccountUserAddress()->getId() &&
                $savedState[$this->getName()]['updated'] >=
                    $checkout->getShippingAddress()->getAccountUserAddress()->getUpdated();
        } elseif (isset($savedState[$this->getName()]['text'])) {
            return $savedState[$this->getName()]['text'] === $checkout->getShippingAddress()->__toString();
        }

        return true;
    }
}
