<?php

namespace OroB2B\Bundle\PaymentBundle\EventListener\Config;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent;

use OroB2B\Bundle\PaymentBundle\DependencyInjection\Configuration;
use OroB2B\Bundle\PaymentBundle\DependencyInjection\OroB2BPaymentExtension;

class PaypalSelectedCountriesListener
{
    /**
     * @param ConfigSettingsUpdateEvent $event
     */
    public function beforeSave(ConfigSettingsUpdateEvent $event)
    {
        $settings = $event->getSettings();

        $settings = $this->handle(
            $settings,
            Configuration::PAYFLOW_GATEWAY_ALLOWED_COUNTRIES_KEY,
            Configuration::PAYFLOW_GATEWAY_SELECTED_COUNTRIES_KEY
        );

        $settings = $this->handle(
            $settings,
            Configuration::PAYPAL_PAYMENTS_PRO_ALLOWED_COUNTRIES_KEY,
            Configuration::PAYPAL_PAYMENTS_PRO_SELECTED_COUNTRIES_KEY
        );

        $event->setSettings($settings);
    }

    /**
     * @param array $settings
     * @param string $allowedCountriesConfigKey
     * @param string $selectedCountriesConfigKey
     * @return array
     */
    protected function handle($settings, $allowedCountriesConfigKey, $selectedCountriesConfigKey)
    {
        $allowedCountriesKey = $this->getFullConfigKey($allowedCountriesConfigKey);
        $selectedCountriesKey = $this->getFullConfigKey($selectedCountriesConfigKey);

        if (array_key_exists($allowedCountriesKey, $settings) &&
            $settings[$allowedCountriesKey]['value'] === Configuration::ALLOWED_COUNTRIES_ALL
        ) {
            $settings[$selectedCountriesKey]['use_parent_scope_value'] = true;
        }

        return $settings;
    }

    /**
     * @param string $configKey
     * @return string
     */
    protected function getFullConfigKey($configKey)
    {
        return OroB2BPaymentExtension::ALIAS . ConfigManager::SECTION_MODEL_SEPARATOR . $configKey;
    }
}
