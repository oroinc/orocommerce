<?php

namespace Oro\Bundle\PaymentBundle\Method\Provider;

use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;

/**
 * Defines the contract for accessing registered payment methods.
 *
 * Implementations provide access to all available payment methods and allow checking
 * whether a specific payment method is registered in the system.
 */
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
}
