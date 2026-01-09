<?php

namespace Oro\Bundle\SEOBundle\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent;
use Oro\Bundle\ConfigBundle\Utils\TreeUtils;
use Oro\Bundle\SEOBundle\DependencyInjection\Configuration;
use Oro\Bundle\SEOBundle\Manager\RobotsTxtDistTemplateManager;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * Sets default value for configuration option "oro_seo.sitemap_robots_txt_template".
 */
class RobotsTxtTemplateSystemConfigFormOptionsListener
{
    private const WEBSITE_SCOPE = 'website';

    public function __construct(
        private ManagerRegistry $doctrine,
        private RobotsTxtDistTemplateManager $robotsTxtDistTemplateManager
    ) {
    }

    public function onFormPreSetData(ConfigSettingsUpdateEvent $event): void
    {
        $robotsTxtTemplateKey = TreeUtils::getConfigKey(
            Configuration::ROOT_NODE,
            Configuration::ROBOTS_TXT_TEMPLATE,
            ConfigManager::SECTION_VIEW_SEPARATOR
        );

        $settings = $event->getSettings();
        if (!array_key_exists($robotsTxtTemplateKey, $settings)) {
            return;
        }

        $robotsTxtTemplateValue = $settings[$robotsTxtTemplateKey]['value'];
        if (
            $robotsTxtTemplateValue === null
            || (is_string($robotsTxtTemplateValue) && $robotsTxtTemplateValue !== '')
        ) {
            return;
        }

        $website = null;
        $configManager = $event->getConfigManager();
        if (self::WEBSITE_SCOPE === $configManager->getScopeEntityName()) {
            $websiteId = $configManager->getScopeId();
            $website = $this->findWebsite($websiteId);

            if ($this->robotsTxtDistTemplateManager->isDistTemplateFileExist($website)) {
                $settings[$robotsTxtTemplateKey][ConfigManager::USE_PARENT_SCOPE_VALUE_KEY] = false;
            }
        }

        $settings[$robotsTxtTemplateKey][ConfigManager::VALUE_KEY] = $this->robotsTxtDistTemplateManager
            ->getDistTemplateContent($website);
        $event->setSettings($settings);
    }

    private function findWebsite(int $id): ?Website
    {
        return $this->doctrine
            ->getRepository(Website::class)
            ->find($id);
    }
}
