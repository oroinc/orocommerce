<?php

namespace Oro\Bundle\PaymentBundle\Provider;

interface PaymentStatusProviderInterface
{
    /**
     * @param object $entity
     * @return string
     */
    public function getPaymentStatus($entity);
}
