<?php

namespace Oro\Bundle\SEOBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroup;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroupRelation;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Migrations\Data\ORM\LoadProductDefaultAttributeFamilyData;
use Oro\Bundle\ProductBundle\Migrations\Data\ORM\MakeProductAttributesTrait;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

/**
 * Updates the SEO attributes configuration.
 */
class LoadProductSeoAttributesAndGroupData extends AbstractFixture implements
    DependentFixtureInterface,
    ContainerAwareInterface
{
    use MakeProductAttributesTrait;

    /** @var string */
    const GROUP_CODE = 'seo';

    /** @var array */
    private $fields = [
        'metaKeywords' => [],
        'metaDescriptions' => [],
        'metaTitles' => [],
    ];

    /**
     * @inheritdoc
     */
    public function load(ObjectManager $manager)
    {
        if (!$this->skipIfAppliedPreviously()) {
            $this->makeProductAttributes(
                $this->fields,
                ExtendScope::OWNER_SYSTEM,
                ['frontend' => ['is_displayable' => false]]
            );
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

        $configManager = $this->getConfigManager();
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
