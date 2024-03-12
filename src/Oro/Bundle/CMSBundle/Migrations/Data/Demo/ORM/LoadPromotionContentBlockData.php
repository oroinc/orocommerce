<?php

declare(strict_types=1);

namespace Oro\Bundle\CMSBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CMSBundle\Entity\ContentBlock;
use Oro\Bundle\CMSBundle\Entity\TextContentVariant;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FrontendBundle\DependencyInjection\Configuration;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Migrations\Data\ORM\LoadOrganizationAndBusinessUnitData;
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
        ];
    }

    public function load(ObjectManager $manager): void
    {
        $data = Yaml::parseFile($this->getFilePathsFromLocator($this->getDataSource()));

        $organization = $manager->getRepository(Organization::class)->getFirst();
        $systemConfigBlock = null;
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

            if ($properties['useForSystemConfig'] ?? false) {
                $systemConfigBlock = $block;
            }

            if (!$this->hasReference($blockAlias)) {
                $this->setReference($blockAlias, $block);
            }
        }

        $manager->flush();

        if ($systemConfigBlock) {
            /** @var ConfigManager $configManager */
            $configManager = $this->container->get('oro_config.global');

            $configKey = Configuration::getConfigKeyByName(Configuration::PROMOTIONAL_CONTENT);
            $value = $configManager->get($configKey);
            if (!$value) {
                $configManager->set($configKey, $systemConfigBlock->getId());
                $configManager->flush();
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
}
