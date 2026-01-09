<?php

namespace Oro\Bundle\CommerceBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CMSBundle\Entity\ContentBlock;
use Oro\Bundle\CMSBundle\Entity\TextContentVariant;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FrontendBundle\Migrations\Data\ORM\AbstractLoadFrontendTheme;
use Oro\Bundle\FrontendBundle\Migrations\Data\ORM\LoadGlobalThemeConfigurationData;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Migrations\Data\ORM\LoadOrganizationAndBusinessUnitData;
use Oro\Bundle\ThemeBundle\DependencyInjection\Configuration;
use Oro\Bundle\ThemeBundle\Entity\ThemeConfiguration;
use Oro\Component\DependencyInjection\ContainerAwareInterface;
use Oro\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Yaml\Yaml;

/**
 * Loads customer dashboards content block data and configures theme configuration for active theme
 */
class LoadCustomerDashboardContentBlocksDemoData extends AbstractLoadFrontendTheme implements
    DependentFixtureInterface,
    ContainerAwareInterface
{
    use ContainerAwareTrait;

    public const string FILE_PATH = '@OroCommerceBundle/Migrations/Data/Demo/ORM/data/dashboard_content_blocks.yml';

    #[\Override]
    public function getDependencies(): array
    {
        return [
            LoadOrganizationAndBusinessUnitData::class,
            LoadGlobalThemeConfigurationData::class
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $data = Yaml::parseFile($this->getFilePaths(static::FILE_PATH));

        $organization = $manager->getRepository(Organization::class)->getFirst();
        $themeConfigBlocks = [];
        foreach ($data as $blockAlias => $properties) {
            $block = $this->findContentBlock($blockAlias, $manager);

            if (!$block) {
                $title = new LocalizedFallbackValue();
                $title->setString($properties['title'] ?? 'Content Block');
                $manager->persist($title);

                $variant = new TextContentVariant();
                $variant->setDefault(true);
                $variant->setContent($properties['content'] ?? '');
                $manager->persist($variant);

                $block = new ContentBlock();
                $block->setOrganization($organization);
                $block->setOwner($organization->getBusinessUnits()->first());
                $block->setAlias($blockAlias);
                $block->addTitle($title);
                $block->addContentVariant($variant);
                $manager->persist($block);
            }

            if ($properties['themeConfigOption'] ?? false) {
                $themeConfigBlocks[$properties['themeConfigOption']] = $block;
            }

            if (!$this->hasReference($blockAlias)) {
                $this->setReference($blockAlias, $block);
            }
        }

        $manager->flush();

        $themeConfigurations = $this->getThemeConfigurations($manager, $organization);
        if (!$themeConfigurations) {
            return;
        }

        $doFlush = false;
        foreach ($themeConfigBlocks as $key => $block) {
            foreach ($themeConfigurations as $themeConfiguration) {
                if (!$themeConfiguration->getConfigurationOption($key)) {
                    $themeConfiguration->addConfigurationOption($key, $block->getId());
                    $doFlush = true;
                }
            }
        }

        if ($doFlush) {
            $manager->flush();
        }
    }

    protected function getFilePaths(string $path): string
    {
        return $this->container->get('file_locator')->locate($path);
    }

    protected function findContentBlock(string $alias, ObjectManager $manager): ?ContentBlock
    {
        return $manager->getRepository(ContentBlock::class)->findOneBy(['alias' => $alias]);
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

    #[\Override]
    protected function getFrontendTheme(): ?string
    {
        return null;
    }
}
