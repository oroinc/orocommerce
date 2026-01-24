<?php

namespace Oro\Bundle\PayPalBundle\Method\Config\Factory;

use Oro\Bundle\PayPalBundle\Entity\PayPalSettings;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalCreditCardConfigInterface;

/**
 * Defines the contract for creating PayPal Credit Card payment method configurations.
 */
interface PayPalCreditCardConfigFactoryInterface extends PayPalConfigFactoryInterface
{
    /**
     * @param PayPalSettings $settings
     * @return PayPalCreditCardConfigInterface
     */
    public function createConfig(PayPalSettings $settings);
}
