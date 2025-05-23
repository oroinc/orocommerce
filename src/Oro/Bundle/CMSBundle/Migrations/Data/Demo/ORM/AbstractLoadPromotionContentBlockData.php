<?php

declare(strict_types=1);

namespace Oro\Bundle\CMSBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CMSBundle\Entity\ContentBlock;
use Oro\Bundle\CMSBundle\Entity\TextContentVariant;
use Oro\Bundle\FrontendBundle\Migrations\Data\ORM\AbstractLoadFrontendTheme;
use Oro\Bundle\FrontendBundle\Migrations\Data\ORM\LoadGlobalThemeConfigurationData;
use Oro\Bundle\LayoutBundle\Layout\Extension\ThemeConfiguration as LayoutThemeConfiguration;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Migrations\Data\ORM\LoadOrganizationAndBusinessUnitData;
use Oro\Bundle\ThemeBundle\Entity\ThemeConfiguration;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Yaml\Yaml;

/**
 * Abstraction for loading promotional content block data and configures system config for organizations
 */
abstract class AbstractLoadPromotionContentBlockData extends AbstractLoadFrontendTheme implements
    DependentFixtureInterface,
    ContainerAwareInterface
{
    use ContainerAwareTrait;

    #[\Override]
    public function getDependencies(): array
    {
        return [
            LoadOrganizationAndBusinessUnitData::class,
            LoadGlobalThemeConfigurationData::class
        ];
    }

    #[\Override]
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
            $themeConfigurations = $this->getThemeConfigurations($manager);
            $key = LayoutThemeConfiguration::buildOptionKey('header', 'promotional_content');
            $doFLush = false;
            foreach ($themeConfigurations as $themeConfiguration) {
                if (!$themeConfiguration->getConfigurationOption($key)) {
                    $themeConfiguration->addConfigurationOption($key, $themeConfigBlock->getId());
                    $doFLush = true;
                }
            }

            if ($doFLush) {
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

    protected function getThemeConfigurations(ObjectManager $manager): array
    {
        $frontendTheme = $this->getFrontendTheme();
        if (!$frontendTheme) {
            return [];
        }

        return $manager->getRepository(ThemeConfiguration::class)->findBy(['theme' => $frontendTheme]);
    }
}
