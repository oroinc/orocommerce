<?php

namespace OroB2B\Bundle\MoneyOrderBundle\Method\Config;

use OroB2B\Bundle\MoneyOrderBundle\DependencyInjection\OroB2BMoneyOrderExtension;
use OroB2B\Bundle\PaymentBundle\Method\Config\AbstractPaymentConfig;
use OroB2B\Bundle\PaymentBundle\DependencyInjection\Configuration as PaymentConfiguration;
use OroB2B\Bundle\MoneyOrderBundle\DependencyInjection\Configuration;
use OroB2B\Bundle\PaymentBundle\Method\Config\CountryAwarePaymentConfigTrait;
use OroB2B\Bundle\PaymentBundle\Method\Config\CurrencyAwarePaymentConfigTrait;

class MoneyOrderConfig extends AbstractPaymentConfig implements MoneyOrderConfigInterface
{
    use CountryAwarePaymentConfigTrait, CurrencyAwarePaymentConfigTrait;

    /** {@inheritdoc} */
    protected function getPaymentExtensionAlias()
    {
        return OroB2BMoneyOrderExtension::ALIAS;
    }

    /** {@inheritdoc} */
    public function getAllowedCountries()
    {
        return (array)$this->getConfigValue(Configuration::MONEY_ORDER_SELECTED_COUNTRIES_KEY);
    }

    /** {@inheritdoc} */
    public function getAllowedCurrencies()
    {
        return (array)$this->getConfigValue(Configuration::MONEY_ORDER_ALLOWED_CURRENCIES);
    }

    /** {@inheritdoc} */
    public function isAllCountriesAllowed()
    {
        return $this->getConfigValue(Configuration::MONEY_ORDER_ALLOWED_COUNTRIES_KEY)
            === PaymentConfiguration::ALLOWED_COUNTRIES_ALL;
    }

    /** {@inheritdoc} */
    public function isEnabled()
    {
        return (bool)$this->getConfigValue(Configuration::MONEY_ORDER_ENABLED_KEY);
    }

    /** {@inheritdoc} */
    public function getOrder()
    {
        return (int)$this->getConfigValue(Configuration::MONEY_ORDER_SORT_ORDER_KEY);
    }

    /** {@inheritdoc} */
    public function getLabel()
    {
        return (string)$this->getConfigValue(Configuration::MONEY_ORDER_LABEL_KEY);
    }

    /** {@inheritdoc} */
    public function getShortLabel()
    {
        return (string)$this->getConfigValue(Configuration::MONEY_ORDER_SHORT_LABEL_KEY);
    }

    /** {@inheritdoc} */
    public function getPayTo()
    {
        return (string)$this->getConfigValue(Configuration::MONEY_ORDER_PAY_TO_KEY);
    }

    /** {@inheritdoc} */
    public function getSendTo()
    {
        return (string)$this->getConfigValue(Configuration::MONEY_ORDER_SEND_TO_KEY);
    }
}
