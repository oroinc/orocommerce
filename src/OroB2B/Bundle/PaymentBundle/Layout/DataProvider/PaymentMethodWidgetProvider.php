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
        if ($entity instanceof PaymentMethodAwareInterface) {
            return sprintf('_%s_%s_widget', $entity->getPaymentMethod(), $suffix);
        }

        return '';
    }
}
