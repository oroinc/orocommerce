<?php

namespace Oro\Bundle\PayPalBundle\Method\Config\Provider;

use Oro\Bundle\PayPalBundle\Method\Config\PayPalCreditCardConfigInterface;

/**
 * Provides PayPal Credit Card payment method configurations.
 *
 * Retrieves and manages Credit Card configuration objects for available payment methods,
 * supporting lookup by payment method identifier.
 */
interface PayPalCreditCardConfigProviderInterface
{
    /**
     * @return PayPalCreditCardConfigInterface[]
     */
    public function getPaymentConfigs();

    /**
     * @param string $identifier
     * @return PayPalCreditCardConfigInterface|null
     */
    public function getPaymentConfig($identifier);

    /**
     * @param string $identifier
     * @return bool
     */
    public function hasPaymentConfig($identifier);
}
