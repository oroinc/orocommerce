<?php

namespace Oro\Bundle\ProductBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroup;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroupRelation;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class LoadProductNewAttributesData extends AbstractFixture implements
    DependentFixtureInterface,
    ContainerAwareInterface
{
    use UserUtilityTrait;
    use ContainerAwareTrait;

    /**
     * @var array
     */
    private static $groups = [
        [
            'groupLabel' => 'General',
            'groupCode' => LoadProductDefaultAttributeFamilyData::GENERAL_GROUP_CODE,
            'attributes' => [
                'featured',
                'newArrival',
                'brand'
            ],
            'groupVisibility' => true
        ]
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadProductDefaultAttributeFamilyData::class
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var ConfigManager $configManager */
        $configManager = $this->container->get('oro_entity_config.config_manager');

        foreach (self::$groups as $groupData) {
            /** @var AttributeGroup $attributeGroup */
            $attributeGroup = $manager->getRepository(AttributeGroup::class)
                ->findOneBy(['code' => $groupData['groupCode']]);
            foreach ($groupData['attributes'] as $attribute) {
                $fieldConfigModel = $configManager->getConfigFieldModel(Product::class, $attribute);
                $fieldId = $fieldConfigModel->getId();
                $attributeRelation = $manager->getRepository(AttributeGroupRelation::class)
                    ->findOneBy(['entityConfigFieldId' => $fieldId, 'attributeGroup' => $attributeGroup]);
                if ($attributeRelation instanceof AttributeGroupRelation) {
                    continue;
                }
                $attributeGroupRelation = new AttributeGroupRelation();
                $attributeGroupRelation->setEntityConfigFieldId($fieldId);
                $attributeGroup->addAttributeRelation($attributeGroupRelation);
                $manager->persist($attributeGroupRelation);
            }
        }

        $manager->flush();
    }
}
