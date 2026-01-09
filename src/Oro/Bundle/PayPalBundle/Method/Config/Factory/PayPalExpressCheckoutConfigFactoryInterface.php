<?php

namespace Oro\Bundle\PayPalBundle\Method\Config\Factory;

use Oro\Bundle\PayPalBundle\Entity\PayPalSettings;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalExpressCheckoutConfigInterface;

/**
 * Defines the contract for creating PayPal Express Checkout payment method configurations.
 */
interface PayPalExpressCheckoutConfigFactoryInterface extends PayPalConfigFactoryInterface
{
    /**
     * @param PayPalSettings $settings
     * @return PayPalExpressCheckoutConfigInterface
     */
    public function createConfig(PayPalSettings $settings);
}
