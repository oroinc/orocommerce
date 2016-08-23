<?php

namespace OroB2B\Bundle\PaymentBundle\Layout\DataProvider;

use OroB2B\Bundle\PaymentBundle\Entity\PaymentMethodAwareInterface;

class PaymentMethodWidgetProvider
{
    const NAME = 'orob2b_payment_method_widget_provider';

    /**
     * @param object $entity
     * @param string $suffix
     * @return string
     */
    public function getPaymentMethodWidgetName($entity, $suffix)
    {
        if (!$entity instanceof PaymentMethodAwareInterface) {
            throw new \InvalidArgumentException(sprintf(
                'Object "%s" must implement interface "%s"',
                is_object($entity) ? get_class($entity) : gettype($entity),
                PaymentMethodAwareInterface::class
            ));
        }

        return sprintf('_%s_%s_widget', $entity->getPaymentMethod(), $suffix);
    }
}
