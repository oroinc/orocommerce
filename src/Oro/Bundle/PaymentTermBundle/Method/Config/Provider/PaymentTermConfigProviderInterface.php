<?php

namespace Oro\Bundle\PaymentTermBundle\Method\Config\Provider;

use Oro\Bundle\PaymentTermBundle\Method\Config\PaymentTermConfigInterface;

/**
 * Provides access to payment term configurations.
 *
 * Implementations of this interface are responsible for retrieving and managing payment term configurations,
 * allowing the payment method system to access configuration data for all available payment terms.
 */
interface PaymentTermConfigProviderInterface
{
    /**
     * @return PaymentTermConfigInterface[]
     */
    public function getPaymentConfigs();

    /**
     * @param string $identifier
     * @return PaymentTermConfigInterface|null
     */
    public function getPaymentConfig($identifier);

    /**
     * @param string $identifier
     * @return bool
     */
    public function hasPaymentConfig($identifier);
}
