<?php

namespace Oro\Bundle\WarehouseBundle\EventListener;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent;
use Oro\Bundle\WarehouseBundle\DependencyInjection\Configuration;
use Oro\Bundle\WarehouseBundle\DependencyInjection\OroWarehouseExtension;
use Oro\Bundle\WarehouseBundle\SystemConfig\WarehouseConfigConverter;

class WarehouseSystemConfigSubscriber
{
    /**
     * @var WarehouseConfigConverter
     */
    protected $converter;

    /**
     * @param WarehouseConfigConverter $converter
     */
    public function __construct(WarehouseConfigConverter $converter)
    {
        $this->converter = $converter;
    }

    /**
     * @param ConfigSettingsUpdateEvent $event
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
        return is_array($settings) && array_key_exists($settingsKey, $settings);
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
                OroWarehouseExtension::ALIAS,
                Configuration::ENABLED_WAREHOUSES,
            ]
        );

        return $settingsKey;
    }
}
