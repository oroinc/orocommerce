<?php

namespace Oro\Bundle\PayPalBundle\Method\Config\Factory;

use Oro\Bundle\PayPalBundle\Entity\PayPalSettings;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalExpressCheckoutConfig;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;

class PayPalExpressCheckoutConfigFactory extends AbstractPayPalConfigFactory implements
    PayPalExpressCheckoutConfigFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function createConfig(PayPalSettings $settings)
    {
        $params = [];
        $channel = $settings->getChannel();

        $params[PayPalExpressCheckoutConfig::PAYMENT_METHOD_IDENTIFIER_KEY] =
            $this->getPaymentMethodIdentifier($channel);

        $params[PayPalExpressCheckoutConfig::ADMIN_LABEL_KEY] = $settings->getExpressCheckoutName();
        $params[PayPalExpressCheckoutConfig::LABEL_KEY] =
            $this->getLocalizedValue($settings->getExpressCheckoutLabels());
        $params[PayPalExpressCheckoutConfig::SHORT_LABEL_KEY] =
            $this->getLocalizedValue($settings->getExpressCheckoutShortLabels());

        $params[PayPalExpressCheckoutConfig::CREDENTIALS_KEY] = $this->getCredentials($settings);
        $params[PayPalExpressCheckoutConfig::TEST_MODE_KEY] = $settings->getTestMode();

        $params[PayPalExpressCheckoutConfig::PURCHASE_ACTION_KEY] = $settings->getExpressCheckoutPaymentAction();

        return new PayPalExpressCheckoutConfig($params);
    }
}
