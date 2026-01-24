<?php

namespace Oro\Bundle\PayPalBundle\Method\Config;

/**
 * Represents PayPal Express Checkout payment method configuration.
 *
 * Extends base PayPal configuration with Express Checkout-specific settings and behavior.
 */
class PayPalExpressCheckoutConfig extends AbstractPayPalConfig implements PayPalExpressCheckoutConfigInterface
{
    const TYPE = 'express_checkout';
}
