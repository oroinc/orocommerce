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
     * @return bool
     */
    protected function isTestMode()
    {
        return (bool)$this->getConfigValue(Configuration::PAYPAL_PAYMENTS_PRO_TEST_MODE_KEY);
    }

    /**
     * @return bool
     */
    public function isAuthorizeOnly()
    {
        return (bool)($this->getConfigValue(Configuration::PAYPAL_PAYMENTS_PRO_ZERO_AMOUNT_AUTHORIZATION_KEY)
            && !$this->getConfigValue(Configuration::PAYPAL_PAYMENTS_PRO_AUTHORIZATION_FOR_REQUIRED_AMOUNT_KEY)
            && (self::AUTHORIZE === $this->getConfigValue(Configuration::PAYPAL_PAYMENTS_PRO_PAYMENT_ACTION_KEY))
        );
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return (bool)$this->getConfigValue(Configuration::PAYPAL_PAYMENTS_PRO_ENABLED_KEY);
    }

    /**
     * @return string
     */
    protected function getPurchaseAction()
    {
        return $this->getConfigValue(Configuration::PAYPAL_PAYMENTS_PRO_PAYMENT_ACTION_KEY);
    }

    /**
     * @return bool
     */
    protected function getAllowedCountries()
    {
        return $this->getConfigValue(Configuration::PAYPAL_PAYMENTS_PRO_SELECTED_COUNTRIES_KEY);
    }

    /**
     * @return bool
     */
    protected function isAuthorizationForRequiredAmountEnabled()
    {
        return $this->getConfigValue(Configuration::PAYPAL_PAYMENTS_PRO_AUTHORIZATION_FOR_REQUIRED_AMOUNT_KEY);
    }

    /**
     * @return bool
     */
    protected function isAllCountriesAllowed()
    {
        return $this->getConfigValue(Configuration::PAYPAL_PAYMENTS_PRO_ALLOWED_COUNTRIES_KEY)
            === Configuration::ALLOWED_COUNTRIES_ALL;
    }

    /**
     * @return bool
     */
    protected function isZeroAmountAuthorizationEnabled()
    {
        return (bool)$this->getConfigValue(Configuration::PAYPAL_PAYMENTS_PRO_ZERO_AMOUNT_AUTHORIZATION_KEY);
    }
}
