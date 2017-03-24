<?php

namespace Oro\Bundle\PricingBundle\EventListener;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent;
use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\PricingBundle\DependencyInjection\Configuration;
use Oro\Bundle\PricingBundle\DependencyInjection\OroPricingExtension;
use Oro\Bundle\PricingBundle\Model\PriceListRelationTriggerHandler;
use Oro\Bundle\PricingBundle\SystemConfig\PriceListConfigConverter;

class PriceListSystemConfigSubscriber
{
    /**
     * @var PriceListConfigConverter
     */
    protected $converter;

    /**
     * @var boolean
     */
    protected $wasChanged = false;

    /**
     * @var PriceListRelationTriggerHandler
     */
    protected $triggerHandler;

    /**
     * @param PriceListConfigConverter $converter
     * @param PriceListRelationTriggerHandler $triggerHandler
     */
    public function __construct(PriceListConfigConverter $converter, PriceListRelationTriggerHandler $triggerHandler)
    {
        $this->converter = $converter;
        $this->triggerHandler = $triggerHandler;
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
        $settings = $event->getSettings();
        if (!array_key_exists('value', $settings)) {
            return;
        }

        $settings['value'] = $this->converter->convertBeforeSave($settings['value']);
        $event->setSettings($settings);

        $this->wasChanged = true;
    }

    /**
     * @param ConfigUpdateEvent $event
     */
    public function updateAfter(ConfigUpdateEvent $event)
    {
        if ($this->wasChanged && $event->getChangeSet()) {
            $this->wasChanged = false;
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
