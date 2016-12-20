<?php

namespace Oro\Bundle\WebCatalogBundle\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent;
use Oro\Bundle\WebCatalogBundle\DependencyInjection\OroWebCatalogExtension;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;

class SystemConfigListener
{
    const SETTING = 'web_catalog';

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param ConfigSettingsUpdateEvent $event
     */
    public function onFormPreSetData(ConfigSettingsUpdateEvent $event)
    {
        $settingsKey = implode(ConfigManager::SECTION_VIEW_SEPARATOR, [OroWebCatalogExtension::ALIAS, self::SETTING]);
        $settings = $event->getSettings();
        if (is_array($settings) && isset($settings[$settingsKey]['value'])) {
            $settings[$settingsKey]['value'] = $this->registry
                ->getManagerForClass(WebCatalog::class)
                ->find(WebCatalog::class, $settings[$settingsKey]['value']);
            $event->setSettings($settings);
        }
    }

    /**
     * @param ConfigSettingsUpdateEvent $event
     */
    public function onSettingsSaveBefore(ConfigSettingsUpdateEvent $event)
    {
        $settingsKey = implode(ConfigManager::SECTION_MODEL_SEPARATOR, [OroWebCatalogExtension::ALIAS, self::SETTING]);
        $settings = $event->getSettings();
        if (is_array($settings)
            && array_key_exists($settingsKey, $settings)
            && $settings[$settingsKey]['value'] instanceof WebCatalog
        ) {
            /** @var WebCatalog $webCatalog */
            $webCatalog = $settings[$settingsKey]['value'];
            $settings[$settingsKey]['value'] = $webCatalog->getId();
            $event->setSettings($settings);
        }
    }
}
