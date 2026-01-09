<?php

namespace Oro\Bundle\PayPalBundle\Method\Config\Provider;

use Oro\Bundle\PayPalBundle\Method\Config\PayPalExpressCheckoutConfigInterface;

/**
 * Provides PayPal Express Checkout payment method configurations.
 *
 * Retrieves and manages Express Checkout configuration objects for available payment methods,
 * supporting lookup by payment method identifier.
 */
interface PayPalExpressCheckoutConfigProviderInterface
{
    /**
     * @return PayPalExpressCheckoutConfigInterface[]
     */
    public function getPaymentConfigs();

    /**
     * @param string $identifier
     * @return PayPalExpressCheckoutConfigInterface|null
     */
    public function getPaymentConfig($identifier);

    /**
     * @param string $identifier
     * @return bool
     */
    public function hasPaymentConfig($identifier);
}
