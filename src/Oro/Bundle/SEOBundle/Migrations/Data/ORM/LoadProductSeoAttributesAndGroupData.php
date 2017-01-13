<?php

namespace Oro\Bundle\SEOBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroup;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroupRelation;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Migrations\Data\ORM\LoadProductDefaultAttributeFamilyData;
use Oro\Bundle\ProductBundle\Migrations\Data\ORM\MakeProductAttributesTrait;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

class LoadProductSeoAttributesAndGroupData extends AbstractFixture implements
    DependentFixtureInterface,
    ContainerAwareInterface
{
    use MakeProductAttributesTrait;

    /** @var string */
    const GROUP_CODE = 'seo';

    /** @var array */
    private $fields = [
        'metaKeywords' => [
            'visible' => false
        ],
        'metaDescriptions' => [
            'visible' => false
        ],
    ];

    /**
     * @inheritdoc
     */
    public function load(ObjectManager $manager)
    {
        if (!$this->skipIfAppliedPreviously()) {
            $this->makeProductAttributes($this->fields);
            $this->addSeoGroup($manager);
        }
    }

    /**
     * If metaKeywords is already attribute then old version of AttributeFamilyData migration was applied.
     *
     * @return bool
     */
    private function skipIfAppliedPreviously()
    {
        $attributeHelper = $this->container->get('oro_entity_config.config.attributes_config_helper');

        return $attributeHelper->isFieldAttribute(Product::class, 'metaKeywords');
    }

    /**
     * @param ObjectManager $manager
     */
    private function addSeoGroup(ObjectManager $manager)
    {
        $attributeFamilyRepository = $manager->getRepository(AttributeFamily::class);

        $defaultFamily =
            $attributeFamilyRepository->findOneBy([
                'code' => LoadProductDefaultAttributeFamilyData::DEFAULT_FAMILY_CODE
            ]);

        $attributeGroup = new AttributeGroup();
        $attributeGroup->setAttributeFamily($defaultFamily);
        $attributeGroup->setDefaultLabel('SEO');
        $attributeGroup->setCode(self::GROUP_CODE);
        $attributeGroup->setIsVisible(false);

        $configManager = $this->container->get('oro_entity_config.config_manager');
        foreach ($this->fields as $attribute => $data) {
            $fieldConfigModel = $configManager->getConfigFieldModel(Product::class, $attribute);
            $attributeGroupRelation = new AttributeGroupRelation();
            $attributeGroupRelation->setEntityConfigFieldId($fieldConfigModel->getId());
            $attributeGroup->addAttributeRelation($attributeGroupRelation);
        }

        $manager->persist($attributeGroup);
        $manager->flush();
    }

    /**
     * @inheritdoc
     */
    public function getDependencies()
    {
        return [
            'Oro\Bundle\ProductBundle\Migrations\Data\ORM\LoadProductDefaultAttributeFamilyData'
        ];
    }
}
