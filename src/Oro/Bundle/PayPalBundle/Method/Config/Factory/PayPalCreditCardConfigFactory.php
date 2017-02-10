<?php

namespace Oro\Bundle\PayPalBundle\Method\Config\Factory;

use Oro\Bundle\PayPalBundle\Entity\PayPalSettings;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalCreditCardConfig;

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

        $params[PayPalCreditCardConfig::PAYMENT_METHOD_IDENTIFIER_KEY] = $this->getPaymentMethodIdentifier($channel);

        $params[PayPalCreditCardConfig::ADMIN_LABEL_KEY] = $channel->getName();
        $params[PayPalCreditCardConfig::LABEL_KEY] = $this->getLocalizedValue($settings->getCreditCardLabels());
        $params[PayPalCreditCardConfig::SHORT_LABEL_KEY] =
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
        $params[PayPalCreditCardConfig::PROXY_HOST_KEY] = $this->getDecryptedValue($settings->getProxyHost());
        $params[PayPalCreditCardConfig::PROXY_PORT_KEY] = $this->getDecryptedValue($settings->getProxyPort());
        $params[PayPalCreditCardConfig::ENABLE_SSL_VERIFICATION_KEY] = $settings->getEnableSSLVerification();

        return new PayPalCreditCardConfig($params);
    }
}
