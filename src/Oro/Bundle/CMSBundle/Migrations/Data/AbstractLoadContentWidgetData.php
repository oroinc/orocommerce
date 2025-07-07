<?php

namespace Oro\Bundle\CMSBundle\Migrations\Data;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FrontendBundle\Migrations\Data\ORM\AbstractLoadFrontendTheme;
use Oro\Bundle\MigrationBundle\Fixture\VersionedFixtureInterface;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ThemeBundle\DependencyInjection\Configuration;
use Oro\Bundle\ThemeBundle\Entity\ThemeConfiguration;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Abstract class for content widgets data fixture.
 */
abstract class AbstractLoadContentWidgetData extends AbstractLoadFrontendTheme implements
    ContainerAwareInterface,
    DependentFixtureInterface,
    VersionedFixtureInterface
{
    protected ?ContainerInterface $container = null;

    #[\Override]
    public function setContainer(?ContainerInterface $container = null): void
    {
        $this->container = $container;
    }

    #[\Override]
    public function getDependencies(): array
    {
        return [
            LoadAdminUserData::class
        ];
    }

    abstract protected function getFilePaths(): string;

    abstract protected function updateContentWidget(
        ObjectManager $manager,
        ContentWidget $contentWidget,
        array $row
    ): void;

    /**
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $data = Yaml::parseFile($this->getFilePaths());
        $organization = $this->getOrganization($manager);

        $themeConfigBlocks = [];

        foreach ($data as $blockAlias => $row) {
            $contentWidget = $this->findContentWidget($manager, $row, $organization);
            if (!$contentWidget) {
                $contentWidget = new ContentWidget();
                $contentWidget->setName($row['name']);
                $contentWidget->setWidgetType($row['type']);
                $contentWidget->setOrganization($organization);
            }


            if (isset($row['description'])) {
                $contentWidget->setDescription($row['description']);
            }
            if (isset($row['layout'])) {
                $contentWidget->setLayout($row['layout']);
            }
            if (isset($row['settings'])) {
                $contentWidget->setSettings(array_merge($row['settings'], $contentWidget->getSettings()));
            }

            if (isset($row['label'])) {
                $contentWidget->setDefaultLabel($row['label']);
            }

            if ($row['themeConfigOption'] ?? false) {
                $themeConfigBlocks[$row['themeConfigOption']] = $contentWidget;
            }

            $this->updateContentWidget($manager, $contentWidget, $row);

            $manager->persist($contentWidget);

            if (!$this->hasReference($blockAlias)) {
                $this->setReference($blockAlias, $contentWidget);
            }
        }

        $manager->flush();

        $themeConfigurations = $this->getThemeConfigurations($manager, $organization);
        if (!$themeConfigurations) {
            return;
        }

        $doFlush = false;
        foreach ($themeConfigBlocks as $key => $contentWidget) {
            foreach ($themeConfigurations as $themeConfiguration) {
                if (!$themeConfiguration->getConfigurationOption($key)) {
                    $themeConfiguration->addConfigurationOption($key, $contentWidget->getId());
                    $doFlush = true;
                }
            }
        }

        if ($doFlush) {
            $manager->flush();
        }
    }

    protected function getOrganization(ObjectManager $manager): Organization
    {
        return $manager->getRepository(Organization::class)->getFirst();
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

    protected function getFilePathsFromLocator(string $path): string
    {
        $locator = $this->container->get('file_locator');
        return $locator->locate($path);
    }

    protected function getThemeConfigurations(ObjectManager $manager, Organization $organization): array
    {
        /** @var ConfigManager $configManager */
        $configManager = $this->container->get('oro_config.global');
        $value = $configManager->get(Configuration::getConfigKeyByName(Configuration::THEME_CONFIGURATION));
        if (!$value) {
            return [];
        }

        return $manager->getRepository(ThemeConfiguration::class)->findBy([
            'id' => (int)$value,
            'organization' => $organization
        ]);
    }
}
