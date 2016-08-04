<?php

namespace Oro\Bundle\PayPalBundle\EventListener\Config;

use Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent;
use Oro\Bundle\PayPalBundle\DependencyInjection\Configuration;

use OroB2B\Bundle\PaymentBundle\DependencyInjection\Configuration as PaymentConfiguration;

class PayPalSelectedCountriesListener
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
        $allowedCountriesKey = Configuration::getFullConfigKey($allowedCountriesConfigKey);
        $selectedCountriesKey = Configuration::getFullConfigKey($selectedCountriesConfigKey);

        if (array_key_exists($allowedCountriesKey, $settings) &&
            array_key_exists($selectedCountriesKey, $settings) &&
            $settings[$allowedCountriesKey]['value'] === PaymentConfiguration::ALLOWED_COUNTRIES_ALL
        ) {
            $settings[$selectedCountriesKey]['use_parent_scope_value'] = true;
        }

        return $settings;
    }
}
