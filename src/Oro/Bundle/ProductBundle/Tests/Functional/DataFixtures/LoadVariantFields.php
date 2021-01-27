<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroupRelation;
use Oro\Bundle\EntityExtendBundle\Entity\Repository\EnumValueRepository;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Schema\OroFrontendTestFrameworkBundleInstaller;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Migrations\Data\ORM\LoadProductDefaultAttributeFamilyData;
use Oro\Bundle\ProductBundle\Migrations\Data\ORM\MakeProductAttributesTrait;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\AbstractFixture;

class LoadVariantFields extends AbstractFixture
{
    use MakeProductAttributesTrait;

    /** @var array */
    private $selectOptions = [
        'Good'     => true,
        'Better' => false,
        'The best' => false,
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $className = ExtendHelper::buildEnumValueClassName(OroFrontendTestFrameworkBundleInstaller::VARIANT_FIELD_CODE);

        /** @var EnumValueRepository $enumRepo */
        $enumRepo = $manager->getRepository($className);

        $priority = 1;
        foreach ($this->selectOptions as $name => $isDefault) {
            $enumOption = $enumRepo->createEnumValue($name, $priority++, $isDefault);
            $manager->persist($enumOption);
        }

        $this->makeProductAttributes(
            [
                OroFrontendTestFrameworkBundleInstaller::VARIANT_FIELD_NAME => []
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
            OroFrontendTestFrameworkBundleInstaller::VARIANT_FIELD_NAME
        );

        $attributeGroupRelation = new AttributeGroupRelation();
        $attributeGroupRelation->setEntityConfigFieldId($variantField->getId());
        $attributeGroup->addAttributeRelation($attributeGroupRelation);
        $manager->persist($defaultFamily);
        $manager->flush();
    }
}
