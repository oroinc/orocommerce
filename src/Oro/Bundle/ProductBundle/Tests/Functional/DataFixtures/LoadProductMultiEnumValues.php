<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroupRelation;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\Fixture\AbstractEnumFixture;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Schema\OroFrontendTestFrameworkBundleInstaller;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Migrations\Data\ORM\LoadProductDefaultAttributeFamilyData;
use Oro\Bundle\ProductBundle\Migrations\Data\ORM\MakeProductAttributesTrait;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

class LoadProductMultiEnumValues extends AbstractEnumFixture implements ContainerAwareInterface
{
    use MakeProductAttributesTrait;

    /**
     * {@inheritdoc}
     */
    protected function getData()
    {
        return [
            'first' => 'First Value',
            'second' => 'Second Value',
            'third' => 'Third Value',
            'fourth' => 'Fourth Value'
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getEnumCode()
    {
        return OroFrontendTestFrameworkBundleInstaller::MULTIENUM_FIELD_CODE;
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        parent::load($manager);

        $this->makeProductAttributes(
            [
                OroFrontendTestFrameworkBundleInstaller::MULTIENUM_FIELD_NAME => []
            ],
            ExtendScope::OWNER_CUSTOM
        );

        $defaultFamily = $manager->getRepository(AttributeFamily::class)
            ->findOneBy(['code' => LoadProductDefaultAttributeFamilyData::DEFAULT_FAMILY_CODE]);

        $this->setReference(LoadProductDefaultAttributeFamilyData::DEFAULT_FAMILY_CODE, $defaultFamily);

        $attributeGroup = $defaultFamily->getAttributeGroup(LoadProductDefaultAttributeFamilyData::GENERAL_GROUP_CODE);

        $configManager = $this->getConfigManager();
        $variantField = $configManager->getConfigFieldModel(
            Product::class,
            OroFrontendTestFrameworkBundleInstaller::MULTIENUM_FIELD_NAME
        );

        $attributeGroupRelation = new AttributeGroupRelation();
        $attributeGroupRelation->setEntityConfigFieldId($variantField->getId());
        $attributeGroup->addAttributeRelation($attributeGroupRelation);

        $manager->persist($defaultFamily);
        $manager->flush();
    }
}
