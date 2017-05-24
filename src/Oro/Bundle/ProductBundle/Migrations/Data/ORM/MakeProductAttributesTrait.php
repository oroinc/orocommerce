<?php

namespace Oro\Bundle\ProductBundle\Migrations\Data\ORM;

use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\ProductBundle\Entity\Product;

use Symfony\Component\DependencyInjection\ContainerAwareTrait;

trait MakeProductAttributesTrait
{
    use ContainerAwareTrait;

    /**
     * @param array $fields
     * @param string $owner
     */
    private function makeProductAttributes(array $fields, $owner = ExtendScope::ORIGIN_SYSTEM)
    {
        $configManager = $this->container->get('oro_entity_config.config_manager');
        $configHelper = $this->container->get('oro_entity_config.config.config_helper');
        $entityManager = $configManager->getEntityManager();

        foreach ($fields as $field => $attributeOptions) {
            $fieldConfigModel = $configManager->getConfigFieldModel(Product::class, $field);

            $options = [
                'attribute' => array_merge([
                    'is_attribute' => true,
                ], $attributeOptions),
                'extend' => [
                    'owner' => $owner
                ]
            ];

            $configHelper->updateFieldConfigs($fieldConfigModel, $options);
            $entityManager->persist($fieldConfigModel);
        }

        $entityManager->flush();
    }
}
