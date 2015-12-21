<?php

namespace OroB2B\Bundle\AccountBundle\EventListener;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent;

use OroB2B\Bundle\AccountBundle\DependencyInjection\OroB2BAccountExtension;
use OroB2B\Bundle\AccountBundle\Visibility\Storage\CategoryVisibilityStorage;

class SystemConfigCategoryVisibilityListener
{
    const SETTING = 'category_visibility';

    /**
     * @var CategoryVisibilityStorage
     */
    protected $categoryVisibilityStorage;

    /**
     * @param CategoryVisibilityStorage $categoryVisibilityStorage
     */
    public function __construct(CategoryVisibilityStorage $categoryVisibilityStorage)
    {
        $this->categoryVisibilityStorage = $categoryVisibilityStorage;
    }

    /**
     * @param ConfigSettingsUpdateEvent $event
     */
    public function onSettingsSaveBefore(ConfigSettingsUpdateEvent $event)
    {
        $settingsKey = $this->getSettingsKey();
        $settings = $event->getSettings();
        if (is_array($settings) && array_key_exists($settingsKey, $settings)) {
            $this->categoryVisibilityStorage->flush();
        }
    }

    /**
     * @return string
     */
    protected function getSettingsKey()
    {
        return implode(ConfigManager::SECTION_VIEW_SEPARATOR, [OroB2BAccountExtension::ALIAS, self::SETTING]);
    }
}
