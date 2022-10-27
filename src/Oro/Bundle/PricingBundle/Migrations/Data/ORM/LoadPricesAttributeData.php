<?php

namespace Oro\Bundle\PricingBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\LocaleBundle\Migrations\Data\ORM\LoadLocalizationData;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Migrations\Data\ORM\LoadProductDefaultAttributeFamilyData;
use Oro\Bundle\ProductBundle\Migrations\Data\ORM\MakeProductAttributesTrait;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

/**
 * Updates entity field configuration for the product price attributes.
 */
class LoadPricesAttributeData extends AbstractFixture implements
    ContainerAwareInterface,
    DependentFixtureInterface
{
    use MakeProductAttributesTrait;

    /**
     * @var array
     */
    private static $groups = [
        [
            'groupLabel' => 'Product Prices',
            'groupCode' => 'prices',
            'attributes' => [
                'productPriceAttributesPrices'
            ],
            'groupVisibility' => true
        ],
    ];

    /**
     * @return array
     */
    public function getDependencies()
    {
        return [
            LoadLocalizationData::class,
            LoadProductDefaultAttributeFamilyData::class
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $defaultAttributeFamily = $manager->getRepository(AttributeFamily::class)
            ->findOneBy(['code' => LoadProductDefaultAttributeFamilyData::DEFAULT_FAMILY_CODE]);

        $configManager = $this->getConfigManager();
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
            ],
            'extend' => [
                'is_extend' => false,
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

        //Add attribute to Prices group and assign it to default family
        $this->addGroupsWithAttributesToFamily(self::$groups, $defaultAttributeFamily, $manager);
    }
}
