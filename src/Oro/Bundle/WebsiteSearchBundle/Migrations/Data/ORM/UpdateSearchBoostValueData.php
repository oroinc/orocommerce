<?php

namespace Oro\Bundle\WebsiteSearchBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EntityConfigBundle\Config\ConfigHelper;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Updates entity field configuration for `search_boost` attributes.
 */
class UpdateSearchBoostValueData extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        if (!$this->container->hasParameter('installed') || !$this->container->getParameter('installed')) {
            return;
        }

        $configManager = $this->container->get('oro_entity_config.config_manager');
        $entityManager = $configManager->getEntityManager();

        $attributes = $this->container->get('oro_entity.doctrine_helper')
            ->getEntityRepositoryForClass(FieldConfigModel::class)
            ->getAllAttributes();

        $configHelper = $this->container->get('oro_entity_config.config.config_helper');

        foreach ($attributes as $attribute) {
            if ($this->isAttributeShouldBeSkipped($configHelper, $attribute)) {
                continue;
            }

            $configHelper->updateFieldConfigs(
                $attribute,
                [
                    'attribute' => [
                        'search_boost' => null,
                    ]
                ]
            );
            $entityManager->persist($attribute);
        }

        $entityManager->flush();
        $configManager->flush();
    }

    private function isAttributeShouldBeSkipped(ConfigHelper $configHelper, FieldConfigModel $attribute): bool
    {
        $attributeConfig = $configHelper->getFieldConfig($attribute, 'attribute');

        return !$attributeConfig->has('search_boost') || !\is_string($attributeConfig->get('search_boost'));
    }
}
