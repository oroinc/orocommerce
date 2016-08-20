<?php

namespace Oro\Bundle\PaymentBundle\Method\Config;

use Oro\Bundle\PaymentBundle\DependencyInjection\Configuration;
use Oro\Bundle\PaymentBundle\DependencyInjection\OroPaymentExtension;

class PaymentTermConfig extends AbstractPaymentConfig implements PaymentTermConfigInterface
{
    use CountryAwarePaymentConfigTrait, CurrencyAwarePaymentConfigTrait;

    /**
     * {@inheritdoc}
     */
    protected function getPaymentExtensionAlias()
    {
        return OroPaymentExtension::ALIAS;
    }

    /**
     * {@inheritdoc}
     */
    public function getAllowedCountries()
    {
        return (array)$this->getConfigValue(Configuration::PAYMENT_TERM_SELECTED_COUNTRIES_KEY);
    }

    /**
     * {@inheritdoc}
     */
    public function isAllCountriesAllowed()
    {
        return $this->getConfigValue(Configuration::PAYMENT_TERM_ALLOWED_COUNTRIES_KEY)
            === Configuration::ALLOWED_COUNTRIES_ALL;
    }

    /**
     * {@inheritdoc}
     */
    public function getAllowedCurrencies()
    {
        return (array)$this->getConfigValue(Configuration::PAYMENT_TERM_ALLOWED_CURRENCIES);
    }

    /**
     * {@inheritdoc}
     */
    public function isEnabled()
    {
        return (bool)$this->getConfigValue(Configuration::PAYMENT_TERM_ENABLED_KEY);
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return (int)$this->getConfigValue(Configuration::PAYMENT_TERM_SORT_ORDER_KEY);
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return (string)$this->getConfigValue(Configuration::PAYMENT_TERM_LABEL_KEY);
    }

    /**
     * {@inheritdoc}
     */
    public function getShortLabel()
    {
        return (string)$this->getConfigValue(Configuration::PAYMENT_TERM_SHORT_LABEL_KEY);
    }
}
