<?php

namespace Oro\Bundle\ProductBundle\Migrations\Data\ORM;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroup;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroupRelation;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Contains methods to simplify fixtures for attributes.
 */
trait MakeProductAttributesTrait
{
    use ContainerAwareTrait;

    /**
     * @param array $fields
     * @param string $owner
     * @param array $otherScopes
     */
    private function makeProductAttributes(array $fields, $owner = ExtendScope::OWNER_SYSTEM, array $otherScopes = [])
    {
        $configManager = $this->getConfigManager();
        $configHelper = $this->container->get('oro_entity_config.config.config_helper');
        $entityManager = $configManager->getEntityManager();

        foreach ($fields as $field => $attributeOptions) {
            $fieldConfigModel = $configManager->getConfigFieldModel(Product::class, $field);

            $options = array_merge(
                [
                    'attribute' => array_merge([
                        'is_attribute' => true,
                    ], $attributeOptions),
                    'extend' => [
                        'owner' => $owner
                    ]
                ],
                $otherScopes
            );

            $configHelper->updateFieldConfigs($fieldConfigModel, $options);
            $entityManager->persist($fieldConfigModel);
        }

        $entityManager->flush();
    }

    private function updateProductAttributes(array $fields)
    {
        $configManager = $this->getConfigManager();
        $configHelper = $this->container->get('oro_entity_config.config.config_helper');
        $entityManager = $configManager->getEntityManager();

        foreach ($fields as $field => $attributeOptions) {
            $fieldConfigModel = $configManager->getConfigFieldModel(Product::class, $field);

            $configHelper->updateFieldConfigs($fieldConfigModel, ['attribute' => $attributeOptions]);
            $entityManager->persist($fieldConfigModel);
        }

        $entityManager->flush();
    }

    private function synchronizeProductAttributesIndexByScope(string $scope): void
    {
        $doctrineHelper = $this->container->get('oro_entity.doctrine_helper');
        $configManager = $this->container->get('oro_entity_config.config_manager');

        $fieldConfigRepository = $doctrineHelper->getEntityRepositoryForClass(FieldConfigModel::class);
        $attributes = $fieldConfigRepository->getAttributesByClass(Product::class);

        $configProvider = $configManager->getProvider($scope);
        foreach ($attributes as $attribute) {
            if ($configManager->hasConfig(Product::class, $attribute->getFieldName())) {
                $config = $configProvider->getConfig(Product::class, $attribute->getFieldName());
                $configManager->persist($config);
            }
        }
        $configManager->flush();
    }

    /**
     * @return ConfigManager
     */
    private function getConfigManager()
    {
        return $this->container->get('oro_entity_config.config_manager');
    }

    /**
     * Iterates over passed groups array assigning corresponding attributes
     * Assigns groups to passed family
     */
    protected function addGroupsWithAttributesToFamily(
        array $groupsWithAttributes,
        AttributeFamily $attributeFamily,
        ObjectManager $manager
    ) {
        $configManager = $this->getConfigManager();

        foreach ($groupsWithAttributes as $groupData) {
            $attributeGroup = $attributeFamily->getAttributeGroup($groupData['groupCode']);
            if (!$attributeGroup) {
                $attributeGroup = new AttributeGroup();
                $attributeGroup->setCode($groupData['groupCode']);
                $attributeFamily->addAttributeGroup($attributeGroup);
            }

            $attributeGroup->setDefaultLabel($groupData['groupLabel']);
            $attributeGroup->setIsVisible($groupData['groupVisibility']);

            $existingAttributes = $attributeGroup->getAttributeRelations()
                ->map(static fn ($attributeGroupRelation) => $attributeGroupRelation->getEntityConfigFieldId())
                ->toArray();

            foreach ($groupData['attributes'] as $attribute) {
                $fieldConfigModel = $configManager->getConfigFieldModel(Product::class, $attribute);
                if (in_array($fieldConfigModel->getId(), $existingAttributes, true)) {
                    // Skips adding attribute to the group because it is already present.
                    continue;
                }

                $attributeGroupRelation = new AttributeGroupRelation();
                $attributeGroupRelation->setEntityConfigFieldId($fieldConfigModel->getId());
                $attributeGroupRelation->setAttributeGroup($attributeGroup);
                $attributeGroup->addAttributeRelation($attributeGroupRelation);
            }
        }

        $manager->persist($attributeFamily);
        $manager->flush();
    }
}
