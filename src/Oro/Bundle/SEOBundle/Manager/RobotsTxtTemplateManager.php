<?php

namespace Oro\Bundle\SEOBundle\Manager;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Utils\TreeUtils;
use Oro\Bundle\SEOBundle\DependencyInjection\Configuration;
use Oro\Component\Website\WebsiteInterface;

/**
 * Provides Robots.txt Template based on the {@see Configuration::ROBOTS_TXT_TEMPLATE} system configuration option value
 * or on robots.txt.dist file content.
 */
class RobotsTxtTemplateManager
{
    public function __construct(
        private RobotsTxtDistTemplateManager $robotsTxtDistTemplateManager,
        private ConfigManager $configManager
    ) {
    }

    public function getTemplateContent(?WebsiteInterface $website = null): string
    {
        $robotsTxtTemplateKey = TreeUtils::getConfigKey(
            Configuration::ROOT_NODE,
            Configuration::ROBOTS_TXT_TEMPLATE
        );

        $templateContent = $this->configManager->get($robotsTxtTemplateKey);
        if (
            $templateContent === null
            || (is_string($templateContent) && $templateContent !== '')
        ) {
            return (string) $templateContent;
        }

        return $this->robotsTxtDistTemplateManager->getDistTemplateContent($website);
    }
}
