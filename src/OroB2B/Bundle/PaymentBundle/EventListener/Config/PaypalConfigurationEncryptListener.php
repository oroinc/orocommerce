<?php

namespace OroB2B\Bundle\PaymentBundle\EventListener\Config;

use Oro\Bundle\ConfigBundle\Event\LoadConfigEvent;
use Oro\Bundle\SecurityBundle\Encoder\Mcrypt;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent;

use OroB2B\Bundle\PaymentBundle\DependencyInjection\Configuration;
use OroB2B\Bundle\PaymentBundle\DependencyInjection\OroB2BPaymentExtension;

class PaypalConfigurationEncryptListener
{
    const PASSWORD_PLACEHOLDER = '*******';

    /** @var MCrypt */
    protected $encoder;

    /**
     * @param Mcrypt $encoder
     */
    public function __construct(Mcrypt $encoder)
    {
        $this->encoder = $encoder;
    }

    /**
     * @param LoadConfigEvent $event
     */
    public function loadConfig(LoadConfigEvent $event)
    {
        $key = $event->getKey();

        if (!$this->isRequiredEncrypt($key)) {
            return;
        }

        $value = $this->getDirectValueFromEvent($event);
        $value = $this->encoder->decryptData($value);

        $this->setDirectValueToEvent($event, $value);
    }

    /**
     * @param ConfigSettingsUpdateEvent $event
     */
    public function beforeSave(ConfigSettingsUpdateEvent $event)
    {
        $settings = $event->getSettings();
        $configManager = $event->getConfigManager();

        foreach ($settings as $configKey => $setting) {
            if (!$this->isRequiredEncrypt($configKey)) {
                continue;
            }

            $value = $settings[$configKey]['value'];

            if ($value === self::PASSWORD_PLACEHOLDER && $this->isRequiredHide($configKey)) {
                $settings[$configKey]['value'] = $configManager->get($configKey);
            } else {
                $settings[$configKey]['value'] = $this->encoder->encryptData($value);
            }
        }

        $event->setSettings($settings);
    }

    /**
     * @param ConfigSettingsUpdateEvent $event
     */
    public function formPreSet(ConfigSettingsUpdateEvent $event)
    {
        $settings = $event->getSettings();

        foreach ($settings as $configKey => $setting) {
            if (!$this->isRequiredHide($configKey, ConfigManager::SECTION_VIEW_SEPARATOR)) {
                continue;
            }

            $settings[$configKey]['value'] = self::PASSWORD_PLACEHOLDER;
        }

        $event->setSettings($settings);
    }

    /**
     * @param string $configFullKey Config model name
     * @return bool
     */
    protected function isRequiredEncrypt($configFullKey)
    {
        list($extensionAlias, $configKey) = $this->parseConfigKey($configFullKey);

        if ($extensionAlias !== OroB2BPaymentExtension::ALIAS) {
            return false;
        }

        return in_array($configKey, $this->getConfigKeysToEncrypt());
    }

    /**
     * @param string $configFullKey Config model name
     * @param string $separator
     * @return bool
     */
    protected function isRequiredHide($configFullKey, $separator = ConfigManager::SECTION_MODEL_SEPARATOR)
    {
        list($extensionAlias, $configKey) = $this->parseConfigKey($configFullKey, $separator);

        if ($extensionAlias !== OroB2BPaymentExtension::ALIAS) {
            return false;
        }

        return in_array($configKey, $this->getConfigKeysToHide());
    }

    /**
     * @param $configFullKey
     * @param string $separator
     * @return array An array with 2 elements: [extensionAlias, configKey]
     */
    protected function parseConfigKey($configFullKey, $separator = ConfigManager::SECTION_MODEL_SEPARATOR)
    {
        return explode($separator, $configFullKey, 2);
    }

    /**
     * Get direct value of config
     *
     * @param LoadConfigEvent $event
     * @return mixed
     */
    protected function getDirectValueFromEvent(LoadConfigEvent $event)
    {
        $eventValue = $event->getValue();

        return $event->isFull() ? $eventValue['value'] : $eventValue;
    }

    /**
     * Set direct value to event
     *
     * @param LoadConfigEvent $event
     * @param mixed $value
     */
    protected function setDirectValueToEvent(LoadConfigEvent $event, $value)
    {
        $eventValue = $event->getValue();
        if ($event->isFull()) {
            $eventValue['value'] = $value;
        } else {
            $eventValue = $value;
        }

        $event->setValue($eventValue);
    }

    /**
     * Keys to encrypt
     *
     * @return array
     */
    protected function getConfigKeysToEncrypt()
    {
        return [
//            Configuration::PAYFLOW_GATEWAY_ENABLED_KEY,
            Configuration::PAYFLOW_GATEWAY_EMAIL_KEY,
            Configuration::PAYFLOW_GATEWAY_PARTNER_KEY,
            Configuration::PAYFLOW_GATEWAY_USER_KEY,
            Configuration::PAYFLOW_GATEWAY_VENDOR_KEY,
            Configuration::PAYFLOW_GATEWAY_PASSWORD_KEY,
//            Configuration::PAYFLOW_GATEWAY_TEST_MODE_KEY,
//            Configuration::PAYFLOW_GATEWAY_USE_PROXY_KEY,
            Configuration::PAYFLOW_GATEWAY_PROXY_HOST_KEY,
            Configuration::PAYFLOW_GATEWAY_PROXY_PORT_KEY,
//            Configuration::PAYPAL_PAYMENTS_PRO_ENABLED_KEY,
            Configuration::PAYPAL_PAYMENTS_PRO_EMAIL_KEY,
            Configuration::PAYPAL_PAYMENTS_PRO_PARTNER_KEY,
            Configuration::PAYPAL_PAYMENTS_PRO_USER_KEY,
            Configuration::PAYPAL_PAYMENTS_PRO_VENDOR_KEY,
            Configuration::PAYPAL_PAYMENTS_PRO_PASSWORD_KEY,
//            Configuration::PAYPAL_PAYMENTS_PRO_TEST_MODE_KEY,
//            Configuration::PAYPAL_PAYMENTS_PRO_USE_PROXY_KEY,
            Configuration::PAYPAL_PAYMENTS_PRO_PROXY_HOST_KEY,
            Configuration::PAYPAL_PAYMENTS_PRO_PROXY_PORT_KEY,
        ];
    }

    /**
     * @return array
     */
    protected function getConfigKeysToHide()
    {
        return [
            // Hide password values
            Configuration::PAYFLOW_GATEWAY_PASSWORD_KEY,
            Configuration::PAYPAL_PAYMENTS_PRO_PASSWORD_KEY,
        ];
    }
}
