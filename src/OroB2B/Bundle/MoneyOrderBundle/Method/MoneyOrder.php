<?php

namespace OroB2B\Bundle\MoneyOrderBundle\Method;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\MoneyOrderBundle\DependencyInjection\Configuration;
use OroB2B\Bundle\MoneyOrderBundle\DependencyInjection\OroB2BMoneyOrderExtension;
use OroB2B\Bundle\PaymentBundle\DependencyInjection\Configuration as PaymentConfiguration;
use OroB2B\Bundle\PaymentBundle\Entity\PaymentTransaction;
use OroB2B\Bundle\PaymentBundle\Method\CountryAwarePaymentMethodTrait;
use OroB2B\Bundle\PaymentBundle\Method\PaymentMethodInterface;

class MoneyOrder implements PaymentMethodInterface
{
    use CountryAwarePaymentMethodTrait;

    const TYPE = 'money_order';

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public function execute($action, PaymentTransaction $paymentTransaction)
    {
        $paymentTransaction->setSuccessful(true);

        return [];
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function getConfigValue($key)
    {
        $key = OroB2BMoneyOrderExtension::ALIAS . ConfigManager::SECTION_MODEL_SEPARATOR . $key;

        return $this->configManager->get($key);
    }

    /**
     * {@inheritdoc}
     */
    public function isEnabled()
    {
        return $this->getConfigValue(Configuration::MONEY_ORDER_ENABLED_KEY);
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return self::TYPE;
    }

    /**
     * @param array $context
     * @return bool
     */
    public function isApplicable(array $context = [])
    {
        return $this->isCountryApplicable($context);
    }

    /**
     * @return bool
     */
    protected function getAllowedCountries()
    {
        return $this->getConfigValue(Configuration::MONEY_ORDER_SELECTED_COUNTRIES_KEY);
    }

    /**
     * @return bool
     */
    protected function isAllCountriesAllowed()
    {
        return $this->getConfigValue(Configuration::MONEY_ORDER_ALLOWED_COUNTRIES_KEY)
        === PaymentConfiguration::ALLOWED_COUNTRIES_ALL;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($actionName)
    {
        return $actionName === self::PURCHASE;
    }
}
