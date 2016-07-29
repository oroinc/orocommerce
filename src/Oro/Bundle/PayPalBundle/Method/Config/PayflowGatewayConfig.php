<?php

namespace Oro\Bundle\PayPalBundle\Method\Config;

use Oro\Bundle\PayPalBundle\DependencyInjection\Configuration;
use Oro\Bundle\PayPalBundle\DependencyInjection\OroPayPalExtension;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;

use OroB2B\Bundle\PaymentBundle\DependencyInjection\Configuration as PaymentConfiguration;
use OroB2B\Bundle\PaymentBundle\Method\Config\AbstractPaymentConfig;
use OroB2B\Bundle\PaymentBundle\Method\Config\CountryAwarePaymentConfigTrait;
use OroB2B\Bundle\PaymentBundle\Method\Config\CurrencyAwarePaymentConfigTrait;

class PayflowGatewayConfig extends AbstractPaymentConfig implements PayflowGatewayConfigInterface
{
    use CountryAwarePaymentConfigTrait, CurrencyAwarePaymentConfigTrait;

    /**
     * @return string
     */
    protected function getPaymentExtensionAlias()
    {
        return OroPayPalExtension::ALIAS;
    }

    /**
     * {@inheritdoc}
     */
    public function getCredentials()
    {
        return [
            Option\Vendor::VENDOR => $this->getConfigValue(Configuration::PAYFLOW_GATEWAY_VENDOR_KEY),
            Option\User::USER => $this->getConfigValue(Configuration::PAYFLOW_GATEWAY_USER_KEY),
            Option\Password::PASSWORD => $this->getConfigValue(Configuration::PAYFLOW_GATEWAY_PASSWORD_KEY),
            Option\Partner::PARTNER => $this->getConfigValue(Configuration::PAYFLOW_GATEWAY_PARTNER_KEY),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function isTestMode()
    {
        return (bool)$this->getConfigValue(Configuration::PAYFLOW_GATEWAY_TEST_MODE_KEY);
    }

    /**
     * {@inheritdoc}
     */
    public function getPurchaseAction()
    {
        return (string)$this->getConfigValue(Configuration::PAYFLOW_GATEWAY_PAYMENT_ACTION_KEY);
    }

    /**
     * {@inheritdoc}
     */
    public function getAllowedCountries()
    {
        return (array)$this->getConfigValue(Configuration::PAYFLOW_GATEWAY_SELECTED_COUNTRIES_KEY);
    }

    /**
     * {@inheritdoc}
     */
    public function isAllCountriesAllowed()
    {
        return $this->getConfigValue(Configuration::PAYFLOW_GATEWAY_ALLOWED_COUNTRIES_KEY)
            === PaymentConfiguration::ALLOWED_COUNTRIES_ALL;
    }

    /**
     * {@inheritdoc}
     */
    public function isEnabled()
    {
        return (bool)$this->getConfigValue(Configuration::PAYFLOW_GATEWAY_ENABLED_KEY);
    }

    /**
     * {@inheritdoc}
     */
    public function isZeroAmountAuthorizationEnabled()
    {
        return (bool)$this->getConfigValue(Configuration::PAYFLOW_GATEWAY_ZERO_AMOUNT_AUTHORIZATION_KEY);
    }

    /**
     * {@inheritdoc}
     */
    public function isAuthorizationForRequiredAmountEnabled()
    {
        return (bool)$this->getConfigValue(Configuration::PAYFLOW_GATEWAY_AUTHORIZATION_FOR_REQUIRED_AMOUNT_KEY);
    }

    /**
     * {@inheritdoc}
     */
    public function getAllowedCurrencies()
    {
        return (array)$this->getConfigValue(Configuration::PAYFLOW_GATEWAY_ALLOWED_CURRENCIES_KEY);
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return (int)$this->getConfigValue(Configuration::PAYFLOW_GATEWAY_SORT_ORDER_KEY);
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return (string)$this->getConfigValue(Configuration::PAYFLOW_GATEWAY_LABEL_KEY);
    }

    /**
     * {@inheritdoc}
     */
    public function getShortLabel()
    {
        return (string)$this->getConfigValue(Configuration::PAYFLOW_GATEWAY_SHORT_LABEL_KEY);
    }

    /**
     * {@inheritdoc}
     */
    public function getAllowedCreditCards()
    {
        return (array)$this->getConfigValue(Configuration::PAYFLOW_GATEWAY_ALLOWED_CC_TYPES_KEY);
    }

    /**
     * {@inheritdoc}
     */
    public function isDebugModeEnabled()
    {
        return (bool)$this->getConfigValue(Configuration::PAYFLOW_GATEWAY_DEBUG_MODE_KEY);
    }

    /**
     * {@inheritdoc}
     */
    public function isUseProxyEnabled()
    {
        return (bool)$this->getConfigValue(Configuration::PAYFLOW_GATEWAY_USE_PROXY_KEY);
    }

    /**
     * {@inheritdoc}
     */
    public function getProxyHost()
    {
        return (string)$this->getConfigValue(Configuration::PAYFLOW_GATEWAY_PROXY_HOST_KEY);
    }

    /**
     * {@inheritdoc}
     */
    public function getProxyPort()
    {
        return (int)$this->getConfigValue(Configuration::PAYFLOW_GATEWAY_PROXY_PORT_KEY);
    }

    /**
     * {@inheritdoc}
     */
    public function isSslVerificationEnabled()
    {
        return (bool)$this->getConfigValue(Configuration::PAYFLOW_GATEWAY_ENABLE_SSL_VERIFICATION_KEY);
    }

    /**
     * {@inheritdoc}
     */
    public function isRequireCvvEntryEnabled()
    {
        return (bool)$this->getConfigValue(Configuration::PAYFLOW_GATEWAY_REQUIRE_CVV_KEY);
    }
}
