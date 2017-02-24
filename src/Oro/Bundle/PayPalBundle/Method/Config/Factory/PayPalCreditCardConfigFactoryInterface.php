<?php

namespace Oro\Bundle\PayPalBundle\Method\Config\Factory;

use Oro\Bundle\PayPalBundle\Entity\PayPalSettings;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalCreditCardConfigInterface;

interface PayPalCreditCardConfigFactoryInterface extends PayPalConfigFactoryInterface
{
    /**
     * @param PayPalSettings $settings
     * @return PayPalCreditCardConfigInterface
     */
    public function createConfig(PayPalSettings $settings);
}
