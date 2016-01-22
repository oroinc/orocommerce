<?php

namespace OroB2B\Bundle\PricingBundle\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent;

use OroB2B\Bundle\PricingBundle\Event\PriceListCollectionChange;
use OroB2B\Bundle\PricingBundle\DependencyInjection\Configuration;
use OroB2B\Bundle\PricingBundle\SystemConfig\PriceListConfigConverter;
use OroB2B\Bundle\PricingBundle\DependencyInjection\OroB2BPricingExtension;

class PriceListSystemConfigSubscriber implements EventSubscriberInterface
{
    /**
     * @var PriceListConfigConverter
     */
    protected $converter;

    /**
     * @var  EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var  boolean
     */
    protected $applicable;

    /** @var  ManagerRegistry */
    protected $registry;

    /**
     * PriceListSystemConfigSubscriber constructor.
     * @param PriceListConfigConverter $converter
     * @param EventDispatcherInterface $eventDispatcher
     * @param ManagerRegistry $registry
     */
    public function __construct(
        PriceListConfigConverter $converter,
        EventDispatcherInterface $eventDispatcher,
        ManagerRegistry $registry
    ) {
        $this->converter = $converter;
        $this->eventDispatcher = $eventDispatcher;
        $this->registry = $registry;
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
    protected function isSettingsApplicable($settings, $settingsKey)
    {
        $this->applicable = is_array($settings) && array_key_exists($settingsKey, $settings);

        return $this->applicable;
    }

    /**
     * @param ConfigUpdateEvent $event
     */
    public function updateAfter(ConfigUpdateEvent $event)
    {
        if ($this->applicable && $event->getChangeSet()) {
            $this->eventDispatcher->dispatch(
                PriceListCollectionChange::BEFORE_CHANGE,
                new PriceListCollectionChange()
            );
            $this->registry->getManager()->flush();
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            ConfigSettingsUpdateEvent::FORM_PRESET => 'formPreSet',
            ConfigSettingsUpdateEvent::BEFORE_SAVE => 'beforeSave',
            ConfigUpdateEvent::EVENT_NAME => 'updateAfter',
        ];
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
                OroB2BPricingExtension::ALIAS,
                Configuration::DEFAULT_PRICE_LISTS,
            ]
        );

        return $settingsKey;
    }
}
