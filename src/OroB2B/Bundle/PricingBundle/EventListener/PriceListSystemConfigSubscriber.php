<?php

namespace OroB2B\Bundle\PricingBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent;

use OroB2B\Bundle\PricingBundle\DependencyInjection\Configuration;
use OroB2B\Bundle\PricingBundle\SystemConfig\PriceListConfigConverterInterface;
use OroB2B\Bundle\PricingBundle\DependencyInjection\OroB2BPricingExtension;
use OroB2B\Bundle\PricingBundle\Form\Type\PriceListSystemConfigType;

class PriceListSystemConfigSubscriber implements EventSubscriberInterface
{
    /** @var PriceListConfigConverterInterface */
    protected $converter;

    /**
     * PriceListSystemConfigSubscriber constructor.
     * @param PriceListConfigConverterInterface $converter
     */
    public function __construct(PriceListConfigConverterInterface $converter)
    {
        $this->converter = $converter;
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
            $settings[$settingKey]['value'] = [
                PriceListSystemConfigType::COLLECTION_FIELD_NAME => $this->converter
                    ->convertFromSaved($settings[$settingKey]['value'])
            ];

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
        if (is_array($settings) && array_key_exists($settingsKey, $settings)) {
            $configs = $settings[$settingsKey]['value'][PriceListSystemConfigType::COLLECTION_FIELD_NAME];
            $settings[$settingsKey]['value'] = $this->converter->convertBeforeSave($configs);
            $event->setSettings($settings);
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            ConfigSettingsUpdateEvent::FORM_PRESET => 'formPreSet',
            ConfigSettingsUpdateEvent::BEFORE_SAVE => 'beforeSave'
        ];
    }

    /**
     * @param string $separator
     * @return string
     */
    protected function getSettingsKey($separator)
    {
        $settingsKey = implode($separator, [
            OroB2BPricingExtension::ALIAS,
            Configuration::DEFAULT_PRICE_LISTS
        ]);

        return $settingsKey;
    }
}
