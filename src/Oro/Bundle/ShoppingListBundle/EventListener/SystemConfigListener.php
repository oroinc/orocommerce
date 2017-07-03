<?php

namespace Oro\Bundle\ShoppingListBundle\EventListener;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent;
use Oro\Bundle\ShoppingListBundle\DependencyInjection\Configuration;
use Oro\Bundle\ShoppingListBundle\DependencyInjection\OroShoppingListExtension;
use Oro\Bundle\ShoppingListBundle\Manager\GuestShoppingListManager;
use Oro\Bundle\UserBundle\Entity\User;

class SystemConfigListener
{
    /** @var GuestShoppingListManager */
    private $guestShoppingListManager;

    /**
     * @param GuestShoppingListManager $guestShoppingListManager
     */
    public function __construct(GuestShoppingListManager $guestShoppingListManager)
    {
        $this->guestShoppingListManager = $guestShoppingListManager;
    }

    /**
     * @param ConfigSettingsUpdateEvent $event
     */
    public function onFormPreSetData(ConfigSettingsUpdateEvent $event)
    {
        $settingsKey = implode(
            ConfigManager::SECTION_VIEW_SEPARATOR,
            [OroShoppingListExtension::ALIAS, Configuration::DEFAULT_GUEST_SHOPPING_LIST_OWNER]
        );
        $settings = $event->getSettings();
        if (is_array($settings) && array_key_exists($settingsKey, $settings)) {
            $settings[$settingsKey]['value'] =
                $this->guestShoppingListManager->getDefaultUser($settings[$settingsKey]['value']);
            $event->setSettings($settings);
        }
    }

    /**
     * @param ConfigSettingsUpdateEvent $event
     */
    public function onSettingsSaveBefore(ConfigSettingsUpdateEvent $event)
    {
        $settings = $event->getSettings();

        if (!array_key_exists('value', $settings)) {
            return;
        }

        if (!is_a($settings['value'], User::class)) {
            return;
        }

        /** @var object $owner */
        $owner = $settings['value'];
        $settings['value'] = $owner->getId();
        $event->setSettings($settings);
    }
}
