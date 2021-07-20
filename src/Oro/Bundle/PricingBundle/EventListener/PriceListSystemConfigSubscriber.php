<?php

namespace Oro\Bundle\PricingBundle\EventListener;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent;
use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\PricingBundle\DependencyInjection\Configuration;
use Oro\Bundle\PricingBundle\DependencyInjection\OroPricingExtension;
use Oro\Bundle\PricingBundle\Model\PriceListRelationTriggerHandler;
use Oro\Bundle\PricingBundle\SystemConfig\PriceListConfigConverter;

/**
 * Normalize and denormalize part of data displayed in Pricing section,
 * tracking changes in configs from this section to handle changes that required rebuilding combined price lists
 */
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

    public function __construct(PriceListConfigConverter $converter, PriceListRelationTriggerHandler $triggerHandler)
    {
        $this->converter = $converter;
        $this->triggerHandler = $triggerHandler;
    }

    public function formPreSet(ConfigSettingsUpdateEvent $event)
    {
        $settingKey = $this->getSettingsKey(ConfigManager::SECTION_VIEW_SEPARATOR);
        $settings = $event->getSettings();
        if (is_array($settings) && array_key_exists($settingKey, $settings)) {
            $settings[$settingKey]['value'] = $this->converter->convertFromSaved($settings[$settingKey]['value']);
            $event->setSettings($settings);
        }
    }

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

    public function updateAfter(ConfigUpdateEvent $event)
    {
        if (!$this->wasChanged) {
            return;
        }

        $handledConfigChanges = \array_intersect_key(
            $event->getChangeSet(),
            \array_flip($this->getConfigNamesRelatedToCombinedPls())
        );

        if ($handledConfigChanges) {
            $this->triggerHandler->handleConfigChange();
        }
        $this->wasChanged = false;
    }

    protected function getConfigNamesRelatedToCombinedPls(): array
    {
        return [
            Configuration::getConfigKeyByName(Configuration::DEFAULT_PRICE_LISTS),
            Configuration::getConfigKeyByName(Configuration::PRICE_LIST_STRATEGIES)
        ];
    }

    /**
     * @param string $separator
     * @return string
     */
    protected function getSettingsKey($separator)
    {
        return implode(
            $separator,
            [
                OroPricingExtension::ALIAS,
                Configuration::DEFAULT_PRICE_LISTS,
            ]
        );
    }
}
