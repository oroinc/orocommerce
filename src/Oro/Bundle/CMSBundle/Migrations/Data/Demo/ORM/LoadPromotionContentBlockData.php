<?php

declare(strict_types=1);

namespace Oro\Bundle\CMSBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CMSBundle\Entity\ContentBlock;
use Oro\Bundle\CMSBundle\Entity\TextContentVariant;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FrontendBundle\Migrations\Data\ORM\LoadGlobalThemeConfiguration;
use Oro\Bundle\LayoutBundle\Layout\Extension\ThemeConfiguration as LayoutThemeConfiguration;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Migrations\Data\ORM\LoadOrganizationAndBusinessUnitData;
use Oro\Bundle\ThemeBundle\Entity\ThemeConfiguration;
use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Yaml\Yaml;

/**
 * Loads promotional content block data and configures system config for organizations
 */
class LoadPromotionContentBlockData extends AbstractFixture implements
    DependentFixtureInterface,
    ContainerAwareInterface
{
    use ContainerAwareTrait;
    use UserUtilityTrait;

    public function getDependencies(): array
    {
        return [
            LoadOrganizationAndBusinessUnitData::class,
            LoadGlobalThemeConfiguration::class
        ];
    }

    public function load(ObjectManager $manager): void
    {
        $data = Yaml::parseFile($this->getFilePathsFromLocator($this->getDataSource()));

        $organization = $manager->getRepository(Organization::class)->getFirst();
        $themeConfigBlock = null;
        foreach ($data as $blockAlias => $properties) {
            $block = $this->findContentBlock($blockAlias, $manager);

            if (!$block) {
                $title = new LocalizedFallbackValue();
                $title->setString($properties['title'] ?? 'Promotional Content');
                $manager->persist($title);

                $variant = new TextContentVariant();
                $variant->setDefault($properties['variant']['default'] ?? false);
                $variant->setContent($properties['variant']['content']);
                $manager->persist($variant);

                $block = new ContentBlock();
                $block->setOrganization($organization);
                $block->setOwner($organization->getBusinessUnits()->first());
                $block->setAlias($blockAlias);
                $block->addTitle($title);
                $block->addContentVariant($variant);
                $manager->persist($block);
            }

            if ($properties['useForThemeConfig'] ?? false) {
                $themeConfigBlock = $block;
            }

            if (!$this->hasReference($blockAlias)) {
                $this->setReference($blockAlias, $block);
            }
        }

        $manager->flush();

        if ($themeConfigBlock) {
            $themeConfiguration = $this->getThemeConfiguration($manager);
            $key = LayoutThemeConfiguration::buildOptionKey('header', 'promotional_content');
            if ($themeConfiguration && !$themeConfiguration->getConfigurationOption($key)) {
                $themeConfiguration->addConfigurationOption($key, $themeConfigBlock->getId());
                $manager->flush();
            }
        }
    }

    protected function getDataSource(): string
    {
        return '@OroCMSBundle/Migrations/Data/Demo/ORM/data/promotional_content.yml';
    }

    protected function getFilePathsFromLocator(string $path): string
    {
        return $this->container->get('file_locator')->locate($path, first: true);
    }

    protected function findContentBlock(string $alias, ObjectManager $manager): ?ContentBlock
    {
        return $manager->getRepository(ContentBlock::class)->findOneBy(['alias' => $alias]);
    }

    protected function getThemeConfiguration(ObjectManager $manager): ?ThemeConfiguration
    {
        /** @var ConfigManager $configManager */
        $configManager = $this->container->get('oro_config.global');
        $value = $configManager->get('oro_theme.theme_configuration');
        if (!$value) {
            return null;
        }

        return $manager->getRepository(ThemeConfiguration::class)->find($value);
    }
}
