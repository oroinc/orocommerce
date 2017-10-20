<?php

namespace Oro\Bundle\PayPalBundle\Method\Config\Factory;

use Oro\Bundle\PayPalBundle\Entity\PayPalSettings;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalExpressCheckoutConfig;

class PayPalExpressCheckoutConfigFactory extends AbstractPayPalConfigFactory implements
    PayPalExpressCheckoutConfigFactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function createConfig(PayPalSettings $settings)
    {
        $params = [];
        $channel = $settings->getChannel();

        $params[PayPalExpressCheckoutConfig::FIELD_PAYMENT_METHOD_IDENTIFIER] =
            $this->getPaymentMethodIdentifier($channel);

        $params[PayPalExpressCheckoutConfig::FIELD_ADMIN_LABEL] = $settings->getExpressCheckoutName();
        $params[PayPalExpressCheckoutConfig::FIELD_LABEL] =
            $this->getLocalizedValue($settings->getExpressCheckoutLabels());
        $params[PayPalExpressCheckoutConfig::FIELD_SHORT_LABEL] =
            $this->getLocalizedValue($settings->getExpressCheckoutShortLabels());

        $params[PayPalExpressCheckoutConfig::CREDENTIALS_KEY] = $this->getCredentials($settings);
        $params[PayPalExpressCheckoutConfig::TEST_MODE_KEY] = $settings->getTestMode();

        $params[PayPalExpressCheckoutConfig::PURCHASE_ACTION_KEY] = $settings->getExpressCheckoutPaymentAction();

        return new PayPalExpressCheckoutConfig($params);
    }
}
