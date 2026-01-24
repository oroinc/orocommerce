<?php

namespace Oro\Bundle\PayPalBundle\Method\Config\Factory;

use Oro\Bundle\PayPalBundle\Entity\PayPalSettings;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalConfigInterface;

/**
 * Defines the contract for creating PayPal payment method configurations.
 */
interface PayPalConfigFactoryInterface
{
    /**
     * @param PayPalSettings $settings
     * @return PayPalConfigInterface
     */
    public function createConfig(PayPalSettings $settings);
}
