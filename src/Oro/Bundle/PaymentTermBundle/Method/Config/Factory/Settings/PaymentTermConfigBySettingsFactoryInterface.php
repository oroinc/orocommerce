<?php

namespace Oro\Bundle\PaymentTermBundle\Method\Config\Factory\Settings;

use Oro\Bundle\PaymentTermBundle\Entity\PaymentTermSettings;
use Oro\Bundle\PaymentTermBundle\Method\Config\PaymentTermConfigInterface;

interface PaymentTermConfigBySettingsFactoryInterface
{
    /**
     * @param PaymentTermSettings $paymentTermSettings
     *
     * @return PaymentTermConfigInterface
     */
    public function createConfigBySettings(PaymentTermSettings  $paymentTermSettings);
}
