<?php

namespace OroB2B\Bundle\PaymentBundle\Method;

use OroB2B\Bundle\PaymentBundle\DependencyInjection\Configuration;
use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option;

class PayPalPaymentsPro extends PayflowGateway
{
    const TYPE = 'paypal_payments_pro';

    /** {@inheritdoc} */
    protected function getCredentials()
    {
        return [
            Option\Vendor::VENDOR => $this->getConfigValue(Configuration::PAYPAL_PAYMENTS_PRO_VENDOR_KEY),
            Option\User::USER => $this->getConfigValue(Configuration::PAYPAL_PAYMENTS_PRO_USER_KEY),
            Option\Password::PASSWORD => $this->getConfigValue(Configuration::PAYPAL_PAYMENTS_PRO_PASSWORD_KEY),
            Option\Partner::PARTNER => $this->getConfigValue(Configuration::PAYPAL_PAYMENTS_PRO_PARTNER_KEY),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function isDebugModeEnabled()
    {
        return (bool)$this->getConfigValue(Configuration::PAYPAL_PAYMENTS_PRO_DEBUG_MODE_KEY);
    }

    /**
     * {@inheritdoc}
     */
    protected function isTestMode()
    {
        return (bool)$this->getConfigValue(Configuration::PAYPAL_PAYMENTS_PRO_TEST_MODE_KEY);
    }

    /**
     * {@inheritdoc}
     */
    protected function isUseProxyEnabled()
    {
        return (bool)$this->getConfigValue(Configuration::PAYPAL_PAYMENTS_PRO_USE_PROXY_KEY);
    }

    /**
     * {@inheritdoc}
     */
    protected function getProxyHost()
    {
        return (string)$this->getConfigValue(Configuration::PAYPAL_PAYMENTS_PRO_PROXY_HOST_KEY);
    }

    /**
     * {@inheritdoc}
     */
    protected function getProxyPort()
    {
        return (int)$this->getConfigValue(Configuration::PAYPAL_PAYMENTS_PRO_PROXY_PORT_KEY);
    }

    /**
     * @return bool
     */
    protected function isSslVerificationEnabled()
    {
        return (bool)$this->getConfigValue(Configuration::PAYPAL_PAYMENTS_PRO_ENABLE_SSL_VERIFICATION_KEY);
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return (bool)$this->getConfigValue(Configuration::PAYPAL_PAYMENTS_PRO_ENABLED_KEY);
    }

    /**
     * {@inheritdoc}
     */
    protected function getPurchaseAction()
    {
        return (string)$this->getConfigValue(Configuration::PAYPAL_PAYMENTS_PRO_PAYMENT_ACTION_KEY);
    }

    /**
     * {@inheritdoc}
     */
    protected function getAllowedCountries()
    {
        return (array)$this->getConfigValue(Configuration::PAYPAL_PAYMENTS_PRO_SELECTED_COUNTRIES_KEY);
    }

    /**
     * {@inheritdoc}
     */
    protected function isAuthorizationForRequiredAmountEnabled()
    {
        return (bool)$this->getConfigValue(Configuration::PAYPAL_PAYMENTS_PRO_AUTHORIZATION_FOR_REQUIRED_AMOUNT_KEY);
    }

    /**
     * {@inheritdoc}
     */
    protected function isAllCountriesAllowed()
    {
        return $this->getConfigValue(Configuration::PAYPAL_PAYMENTS_PRO_ALLOWED_COUNTRIES_KEY)
            === Configuration::ALLOWED_COUNTRIES_ALL;
    }

    /**
     * {@inheritdoc}
     */
    protected function isZeroAmountAuthorizationEnabled()
    {
        return (bool)$this->getConfigValue(Configuration::PAYPAL_PAYMENTS_PRO_ZERO_AMOUNT_AUTHORIZATION_KEY);
    }

    /**
     * {@inheritdoc}
     */
    protected function getAllowedCurrencies()
    {
        return (array)$this->getConfigValue(Configuration::PAYPAL_PAYMENTS_PRO_ALLOWED_CURRENCIES);
    }
}
