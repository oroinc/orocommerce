<?php

namespace Oro\Bundle\CMSBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Updates "draft" config on cms page properties.
 */
class LoadDraftablePageConfig extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    public function load(ObjectManager $manager): void
    {
        $configManager = $this->getConfigManager();
        $this->updatePropertyConfig($configManager, 'metaTitles');
        $this->updatePropertyConfig($configManager, 'metaDescriptions');
        $this->updatePropertyConfig($configManager, 'metaKeywords');
        $configManager->flush();
    }

    private function updatePropertyConfig(ConfigManager $configManager, string $fieldName): void
    {
        $draftProvider = $configManager->getProvider('draft');
        $config = $draftProvider->getConfig(Page::class, $fieldName);
        $config->set('draftable', true);
        $configManager->persist($config);
    }

    private function getConfigManager(): ConfigManager
    {
        return $this->container->get('oro_entity_config.config_manager');
    }
}
