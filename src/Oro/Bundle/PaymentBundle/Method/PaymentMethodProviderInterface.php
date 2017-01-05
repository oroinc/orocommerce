<?php

namespace Oro\Bundle\PaymentBundle\Method;

interface PaymentMethodProviderInterface
{
    /**
     * @return PaymentMethodInterface[]
     */
    public function getPaymentMethods();

    /**
     * @param string $identifier
     * @return PaymentMethodInterface
     */
    public function getPaymentMethod($identifier);

    /**
     * @param string $identifier
     * @return bool
     */
    public function hasPaymentMethod($identifier);

    /**
     * @return string
     */
    public function getType();
}
