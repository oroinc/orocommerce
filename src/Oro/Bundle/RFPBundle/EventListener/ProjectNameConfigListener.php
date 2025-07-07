<?php

namespace Oro\Bundle\RFPBundle\EventListener;

use Oro\Bundle\CacheBundle\Provider\MemoryCache;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Event\ConfigGetEvent;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\WebsiteBundle\Provider\WebsiteProviderInterface;

/**
 * Calculates "is project name enabled" config option for the back-office
 * when it is enabled for an organization or for at least one website within an organization.
 */
class ProjectNameConfigListener
{
    public function __construct(
        private readonly TokenAccessorInterface $tokenAccessor,
        private readonly WebsiteProviderInterface $websiteProvider,
        private readonly ?ConfigManager $websiteConfigManager,
        private readonly MemoryCache $memoryCache
    ) {
    }

    public function loadConfig(ConfigGetEvent $event): void
    {
        $user = $this->tokenAccessor->getUser();
        if (!$user instanceof User) {
            return;
        }

        $cacheKey = 'project_name_config_listener:' . $event->getKey();
        if ($this->memoryCache->has($cacheKey)) {
            return;
        }

        $this->memoryCache->set($cacheKey, true);

        if (!$event->isFull() && !$event->getValue() && 'organization' === $event->getScope()) {
            $this->checkWebsites($event);
        }
    }

    private function checkWebsites(ConfigGetEvent $event): void
    {
        if (null === $this->websiteConfigManager) {
            return;
        }

        $configKey = $event->getKey();
        $websites = $this->websiteProvider->getWebsites();
        foreach ($websites as $website) {
            if ($this->websiteConfigManager->get($configKey, false, false, $website)) {
                $event->setValue(true);
                break;
            }
        }
    }
}
