<?php

namespace Oro\Bundle\CheckoutBundle\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\CheckoutBundle\DependencyInjection\Configuration;
use Oro\Bundle\CheckoutBundle\DependencyInjection\OroCheckoutExtension;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent;
use Oro\Bundle\ConfigBundle\Utils\TreeUtils;
use Oro\Bundle\UserBundle\Entity\User;

class SystemConfigListener
{
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
        $settingsKey = $this->getSettingsKey();
        $settings = $event->getSettings();
        if (isset($settings[$settingsKey]['value'])) {
            $settings[$settingsKey]['value'] = $this->registry
                ->getManagerForClass(User::class)
                ->find(User::class, $settings[$settingsKey]['value']);
            $event->setSettings($settings);
        }
    }

    /**
     * @param ConfigSettingsUpdateEvent $event
     */
    public function onSettingsSaveBefore(ConfigSettingsUpdateEvent $event)
    {
        $settings = $event->getSettings();
        $settingsKey = $this->getSettingsKey();
        if (!isset($settings[$settingsKey]['value'])) {
            return;
        }
        if (!is_a($settings[$settingsKey]['value'], User::class)) {
            return;
        }
        /** @var User $owner */
        $owner = $settings[$settingsKey]['value'];
        $settings[$settingsKey]['value'] = $owner->getId();
        $event->setSettings($settings);
    }

    /**
     * @return string
     */
    private function getSettingsKey()
    {
        return TreeUtils::getConfigKey(
            OroCheckoutExtension::ALIAS,
            Configuration::DEFAULT_GUEST_CHECKOUT_OWNER,
            ConfigManager::SECTION_VIEW_SEPARATOR
        );
    }
}
