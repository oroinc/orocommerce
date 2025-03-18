<?php

namespace Oro\Bundle\CMSBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Bundle\FrontendBundle\Migrations\Data\ORM\LoadGlobalThemeConfigurationData;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ThemeBundle\DependencyInjection\Configuration;
use Oro\Bundle\ThemeBundle\Entity\ThemeConfiguration;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Yaml\Yaml;

/**
 * Sets product segments for theme configuration for already installed applications
 */
class SetProductSegmentsForThemeConfiguration extends AbstractFixture implements
    DependentFixtureInterface,
    ContainerAwareInterface
{
    use ContainerAwareTrait;

    public const string FILE_PATH = '@OroCMSBundle/Migrations/Data/ORM/data/content_widgets.yml';

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        if (!$this->container->get(ApplicationState::class)->isInstalled()) {
            return;
        }

        $organization = $manager->getRepository(Organization::class)->getFirst();
        $themeConfiguration = $this->getThemeConfiguration($manager);
        if (!$themeConfiguration) {
            return;
        }

        $doFlush = false;
        $data = Yaml::parseFile($this->getFilePaths(static::FILE_PATH));

        foreach ($data as $row) {
            $contentWidget = $this->findContentWidget($manager, $row, $organization);
            if (!$contentWidget) {
                continue;
            }

            if (!($row['themeConfigOption'] ?? false)) {
                continue;
            }

            if (!$themeConfiguration->getConfigurationOption($row['themeConfigOption'])) {
                $themeConfiguration->addConfigurationOption($row['themeConfigOption'], $contentWidget->getId());
                $doFlush = true;
            }
        }

        if ($doFlush) {
            $manager->flush();
        }
    }

    protected function findContentWidget(ObjectManager $manager, array $row, Organization $organization): ?ContentWidget
    {
        if (empty($row['name'])) {
            return null;
        }

        return $manager->getRepository(ContentWidget::class)->findOneBy([
            'name' => $row['name'],
            'organization' => $organization
        ]);
    }

    protected function getFilePaths(string $path): string
    {
        return $this->container->get('file_locator')->locate($path);
    }

    protected function getThemeConfiguration(ObjectManager $manager): ?ThemeConfiguration
    {
        /** @var ConfigManager $configManager */
        $configManager = $this->container->get('oro_config.global');
        $value = $configManager->get(Configuration::getConfigKeyByName(Configuration::THEME_CONFIGURATION));
        if (!$value) {
            return null;
        }

        return $manager->getRepository(ThemeConfiguration::class)->find($value);
    }

    public function getDependencies(): array
    {
        return [
            LoadAdminUserData::class,
            LoadGlobalThemeConfigurationData::class,
            LoadProductsSegmentContentWidgetData::class
        ];
    }
}
