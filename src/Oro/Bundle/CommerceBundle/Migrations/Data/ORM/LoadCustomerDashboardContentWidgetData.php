<?php

namespace Oro\Bundle\CommerceBundle\Migrations\Data\ORM;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\CMSBundle\Migrations\Data\AbstractLoadContentWidgetData;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FrontendBundle\Migrations\Data\ORM\LoadGlobalThemeConfigurationData;
use Oro\Bundle\ThemeBundle\DependencyInjection\Configuration;
use Oro\Bundle\ThemeBundle\Entity\ThemeConfiguration;

/**
 * Loads customer dashboards content widget data and configures system config for organizations
 */
class LoadCustomerDashboardContentWidgetData extends AbstractLoadContentWidgetData
{
    private ?ThemeConfiguration $themeConfiguration = null;

    public function getVersion(): string
    {
        return '1.0';
    }

    public function getDependencies(): array
    {
        return [
            ...parent::getDependencies(),
            LoadGlobalThemeConfigurationData::class
        ];
    }

    #[\Override]
    protected function getFilePaths(): string
    {
        return $this->getFilePathsFromLocator('@OroCommerceBundle/Migrations/Data/ORM/data/content_widgets.yml');
    }

    #[\Override]
    protected function updateContentWidget(ObjectManager $manager, ContentWidget $contentWidget, array $row): void
    {
        return;
    }

    protected function getThemeConfiguration(ObjectManager $manager): ?ThemeConfiguration
    {
        if (!$this->themeConfiguration) {
            /** @var ConfigManager $configManager */
            $configManager = $this->container->get('oro_config.global');
            $value = $configManager->get(Configuration::getConfigKeyByName(Configuration::THEME_CONFIGURATION));
            if (!$value) {
                return null;
            }

            $this->themeConfiguration = $manager->getRepository(ThemeConfiguration::class)->find($value);
        }

        return $this->themeConfiguration;
    }
}
