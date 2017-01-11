<?php

namespace Oro\Bundle\ProductBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\LocaleBundle\Migrations\Data\ORM\LoadLocalizationData;
use Oro\Bundle\ProductBundle\Entity\Product;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class LoadDefaultAttributesData extends AbstractFixture implements
    ContainerAwareInterface,
    DependentFixtureInterface
{
    use ContainerAwareTrait;

    /**
     * @var array
     */
    private $fields = [
        'metaKeywords' => [
            'visible' => false
        ],
        'metaDescriptions' => [
            'visible' => false
        ],
        'inventory_status' => [
            'visible' => true
        ]
    ];

    public function getDependencies()
    {
        return [
            LoadLocalizationData::class
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->addAttributes();
        $this->addPricesAttribute();
    }

    private function addPricesAttribute()
    {
        $configManager = $this->container->get('oro_entity_config.config_manager');
        $configHelper = $this->container->get('oro_entity_config.config.config_helper');
        $entityManager = $configManager->getEntityManager();

        $fieldConfigModel = $configManager->createConfigFieldModel(
            Product::class,
            'productPriceAttributesPrices',
            RelationType::TO_MANY
        );

        $options = [
            'attribute' => [
                'is_attribute' => true,
                'searchable' => false,
                'filterable' => false,
                'sortable' => false,
                'enabled' => true,
                'visible' => true
            ],
            'extend' => [
                'is_extend' => false,
                'origin' => ExtendScope::ORIGIN_SYSTEM,
                'owner' => ExtendScope::OWNER_SYSTEM,
                'state' => ExtendScope::STATE_ACTIVE,
                'is_serialized' => false,
                'is_deleted' => false
            ],
        ];

        $fieldConfigModel->setCreated(new \DateTime());
        $configHelper->updateFieldConfigs($fieldConfigModel, $options);

        $entityManager->persist($fieldConfigModel);
        $entityManager->flush();
        $configManager->flush();

        $translationHelper = $this->container->get('oro_entity_config.translation.helper');
        $translationHelper->saveTranslations([
            'oro.product.product_price_attributes_prices.label' => 'Product prices'
        ]);
    }

    private function addAttributes()
    {
        $configManager = $this->container->get('oro_entity_config.config_manager');
        $configHelper = $this->container->get('oro_entity_config.config.config_helper');
        $entityManager = $configManager->getEntityManager();

        foreach ($this->fields as $field => $attributeOptions) {
            $fieldConfigModel = $configManager->getConfigFieldModel(Product::class, $field);

            $options = [
                'attribute' => array_merge([
                    'is_attribute' => true,
                ], $attributeOptions),
                'extend' => [
                    'owner' => ExtendScope::ORIGIN_SYSTEM
                ]
            ];

            $configHelper->updateFieldConfigs($fieldConfigModel, $options);
            $entityManager->persist($fieldConfigModel);
        }

        $entityManager->flush();
    }
}
