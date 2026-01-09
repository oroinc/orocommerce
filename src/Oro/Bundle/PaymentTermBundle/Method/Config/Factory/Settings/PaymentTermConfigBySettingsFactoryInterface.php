<?php

namespace Oro\Bundle\PaymentTermBundle\Method\Config\Factory\Settings;

use Oro\Bundle\PaymentTermBundle\Entity\PaymentTermSettings;
use Oro\Bundle\PaymentTermBundle\Method\Config\PaymentTermConfigInterface;

/**
 * Factory interface for creating payment term configurations from payment term settings.
 *
 * Implementations of this interface are responsible for converting {@see PaymentTermSettings} entities
 * into {@see PaymentTermConfigInterface} instances that can be used by the payment method system.
 */
interface PaymentTermConfigBySettingsFactoryInterface
{
    /**
     * @param PaymentTermSettings $paymentTermSettings
     *
     * @return PaymentTermConfigInterface
     */
    public function createConfigBySettings(PaymentTermSettings $paymentTermSettings);
}
