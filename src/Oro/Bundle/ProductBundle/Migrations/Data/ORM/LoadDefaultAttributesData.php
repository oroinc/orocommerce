<?php

namespace Oro\Bundle\ProductBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\LocaleBundle\Migrations\Data\ORM\LoadLocalizationData;
use Oro\Bundle\ProductBundle\Entity\Product;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;

class LoadDefaultAttributesData extends AbstractFixture implements
    ContainerAwareInterface,
    DependentFixtureInterface
{
    use MakeProductAttributesTrait;

    /**
     * @var array
     */
    private $fields = [
        'inventory_status' => [
            'visible' => false
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
        $this->makeProductAttributes($this->fields);
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
}
