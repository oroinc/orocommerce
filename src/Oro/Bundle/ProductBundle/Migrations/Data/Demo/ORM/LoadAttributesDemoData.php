<?php

namespace Oro\Bundle\ProductBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\ProductBundle\Entity\Product;

class LoadAttributesDemoData extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @var array
     */
    private static $attributes = [
       [
            'attributeName' => 'system_attribute_1',
            'labelName' => 'System attribute 1',
            'options' => [
                'attribute' => [
                    'is_system' => true,
                ],
                'extend' => [
                    'origin' => ExtendScope::ORIGIN_SYSTEM,
                    'owner' => ExtendScope::OWNER_SYSTEM,
                    'state' => ExtendScope::STATE_ACTIVE,
                    'is_serialized' => true
                ],
            ]
        ],
        [
            'attributeName' => 'system_attribute_2',
            'labelName' => 'System attribute 2',
            'options' => [
                'attribute' => [
                    'is_system' => true,
                ],
                'extend' => [
                    'origin' => ExtendScope::OWNER_SYSTEM,
                    'owner' => ExtendScope::OWNER_SYSTEM,
                    'state' => ExtendScope::STATE_ACTIVE,
                    'is_serialized' => true
                ],
            ]
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $configManager = $this->container->get('oro_entity_config.config_manager');
        $configHelper = $this->container->get('oro_entity_config.config.config_helper');
        /** @var ConfigProvider $entityConfigProvider */
        $entityConfigProvider = $this->container->get('oro_entity_config.provider.extend');

        $entityModel = $configManager->getConfigEntityModel(Product::class);
        $entityManager = $configManager->getEntityManager();
        $labelTranslations = [];
        foreach (self::$attributes as $attributeData) {
            $labelKey = sprintf('oro.product.%s.label', $attributeData['attributeName']);
            $labelTranslations[$labelKey] = $attributeData['labelName'];

            $attributeData['options']['entity']['label'] = $labelKey;

            $attribute = $configManager->createConfigFieldModel(
                $entityModel->getClassName(),
                $attributeData['attributeName'],
                'bigint'
            );
            $attribute->setCreated(new \DateTime());
            $options = array_merge_recursive(
                $attributeData['options'],
                [
                    'attribute' => [
                        'is_attribute' => true
                    ],
                    'extend' => [
                        'is_extend' => true
                    ]
                ]
            );

            $configHelper->updateFieldConfigs($attribute, $options);

            $entityManager->persist($attribute);

            $entityConfigId = $configManager->getConfigIdByModel($attribute, 'entity');
            $config = $entityConfigProvider->getConfigById($entityConfigId);
            $config->set('label', $labelKey);

            $configManager->persist($config);
        }

        $configManager->flush();
        $entityManager->flush();

        $translationHelper = $this->container->get('oro_entity_config.translation.helper');
        $translationHelper->saveTranslations($labelTranslations);
    }
}
