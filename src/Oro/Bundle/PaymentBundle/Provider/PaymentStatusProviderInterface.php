<?php

namespace Oro\Bundle\PaymentBundle\Provider;

/**
 * Will be removed in v7.0, use {@link PaymentStatusCalculatorInterface} instead.
 */
interface PaymentStatusProviderInterface
{
    /**
     * @param object $entity
     * @return string
     */
    public function getPaymentStatus($entity);
}
