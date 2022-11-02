<?php

namespace Oro\Bundle\PayPalBundle\Method\Config\Factory;

use Oro\Bundle\PayPalBundle\Entity\PayPalSettings;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalCreditCardConfig;

/**
 * Config factory for PayPal Payflow Gateway / Payments Pro payment method
 */
class PayPalCreditCardConfigFactory extends AbstractPayPalConfigFactory implements
    PayPalCreditCardConfigFactoryInterface
{
    /**
     * @param PayPalSettings $settings
     *
     * @return PayPalCreditCardConfig
     * @throws \InvalidArgumentException
     */
    public function createConfig(PayPalSettings $settings)
    {
        $params = [];
        $channel = $settings->getChannel();

        $params[PayPalCreditCardConfig::FIELD_PAYMENT_METHOD_IDENTIFIER] = $this->getPaymentMethodIdentifier($channel);

        $params[PayPalCreditCardConfig::FIELD_ADMIN_LABEL] = $channel->getName();
        $params[PayPalCreditCardConfig::FIELD_LABEL] = $this->getLocalizedValue($settings->getCreditCardLabels());
        $params[PayPalCreditCardConfig::FIELD_SHORT_LABEL] =
            $this->getLocalizedValue($settings->getCreditCardShortLabels());
        $params[PayPalCreditCardConfig::ALLOWED_CREDIT_CARD_TYPES_KEY] = $settings->getAllowedCreditCardTypes();

        $params[PayPalCreditCardConfig::CREDENTIALS_KEY] = $this->getCredentials($settings);
        $params[PayPalCreditCardConfig::TEST_MODE_KEY] = $settings->getTestMode();

        $params[PayPalCreditCardConfig::PURCHASE_ACTION_KEY] = $settings->getCreditCardPaymentAction();
        $params[PayPalCreditCardConfig::DEBUG_MODE_KEY] = $settings->getDebugMode();
        $params[PayPalCreditCardConfig::REQUIRE_CVV_ENTRY_KEY] = $settings->getRequireCVVEntry();
        $params[PayPalCreditCardConfig::ZERO_AMOUNT_AUTHORIZATION_KEY] = $settings->getZeroAmountAuthorization();
        $params[PayPalCreditCardConfig::AUTHORIZATION_FOR_REQUIRED_AMOUNT_KEY] =
            $settings->getAuthorizationForRequiredAmount();

        $params[PayPalCreditCardConfig::USE_PROXY_KEY] = $settings->getUseProxy();
        $params[PayPalCreditCardConfig::PROXY_HOST_KEY] = $settings->getProxyHost();
        $params[PayPalCreditCardConfig::PROXY_PORT_KEY] = $settings->getProxyPort();
        $params[PayPalCreditCardConfig::ENABLE_SSL_VERIFICATION_KEY] = $settings->getEnableSSLVerification();

        return new PayPalCreditCardConfig($params);
    }
}
