<?php

namespace Oro\Bundle\PricingBundle\EventListener;

use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent;
use Oro\Bundle\PricingBundle\DependencyInjection\Configuration;
use Oro\Bundle\PricingBundle\SystemConfig\PriceListConfigConverter;
use Oro\Bundle\PricingBundle\DependencyInjection\OroPricingExtension;
use Oro\Bundle\PricingBundle\Model\PriceListChangeTriggerHandler;

class PriceListSystemConfigSubscriber
{
    /**
     * @var PriceListConfigConverter
     */
    protected $converter;

    /**
     * @var boolean
     */
    protected $isApplicable;

    /**
     * @var PriceListChangeTriggerHandler
     */
    protected $triggerHandler;

    /**
     * @param PriceListConfigConverter $converter
     * @param PriceListChangeTriggerHandler $triggerHandler
     */
    public function __construct(PriceListConfigConverter $converter, PriceListChangeTriggerHandler $triggerHandler)
    {
        $this->converter = $converter;
        $this->triggerHandler = $triggerHandler;
    }


    /**
     * @param ConfigSettingsUpdateEvent $event
     * @return array
     */
    public function formPreSet(ConfigSettingsUpdateEvent $event)
    {
        $settingKey = $this->getSettingsKey(ConfigManager::SECTION_VIEW_SEPARATOR);
        $settings = $event->getSettings();
        if (is_array($settings) && array_key_exists($settingKey, $settings)) {
            $settings[$settingKey]['value'] = $this->converter->convertFromSaved($settings[$settingKey]['value']);
            $event->setSettings($settings);
        }
    }

    /**
     * @param ConfigSettingsUpdateEvent $event
     */
    public function beforeSave(ConfigSettingsUpdateEvent $event)
    {
        $settingsKey = $this->getSettingsKey(ConfigManager::SECTION_MODEL_SEPARATOR);
        $settings = $event->getSettings();
        if ($this->isSettingsApplicable($settings, $settingsKey)) {
            $settings[$settingsKey]['value'] = $this->converter->convertBeforeSave($settings[$settingsKey]['value']);
            $event->setSettings($settings);
        }
    }

    /**
     * @param array $settings
     * @param string $settingsKey
     * @return bool
     */
    protected function isSettingsApplicable(array $settings, $settingsKey)
    {
        $this->isApplicable = is_array($settings) && array_key_exists($settingsKey, $settings);

        return $this->isApplicable;
    }

    /**
     * @param ConfigUpdateEvent $event
     */
    public function updateAfter(ConfigUpdateEvent $event)
    {
        if ($this->isApplicable && $event->getChangeSet()) {
            $this->triggerHandler->handleConfigChange();
        }
    }

    /**
     * @param string $separator
     * @return string
     */
    protected function getSettingsKey($separator)
    {
        $settingsKey = implode(
            $separator,
            [
                OroPricingExtension::ALIAS,
                Configuration::DEFAULT_PRICE_LISTS,
            ]
        );

        return $settingsKey;
    }
}
