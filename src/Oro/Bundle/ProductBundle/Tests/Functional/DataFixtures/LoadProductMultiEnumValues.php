<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroupRelation;
use Oro\Bundle\EntityExtendBundle\Entity\Repository\EnumValueRepository;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Migrations\Data\ORM\LoadProductDefaultAttributeFamilyData;
use Oro\Bundle\ProductBundle\Migrations\Data\ORM\MakeProductAttributesTrait;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

class LoadProductMultiEnumValues extends AbstractFixture implements ContainerAwareInterface
{
    use MakeProductAttributesTrait;

    private const DATA = [
        'first' => 'First Value',
        'second' => 'Second Value',
        'third' => 'Third Value',
        'fourth' => 'Fourth Value'
    ];

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager): void
    {
        /** @var EnumValueRepository $enumRepo */
        $enumRepo = $manager->getRepository(ExtendHelper::buildEnumValueClassName('multienum_code'));
        $priority = 1;
        foreach (self::DATA as $id => $name) {
            $enumValue = $enumRepo->createEnumValue($name, $priority++, false, $id);
            $manager->persist($enumValue);
        }
        $manager->flush();

        $this->makeProductAttributes(['multienum_field' => []], ExtendScope::OWNER_CUSTOM);

        $defaultFamily = $manager->getRepository(AttributeFamily::class)
            ->findOneBy(['code' => LoadProductDefaultAttributeFamilyData::DEFAULT_FAMILY_CODE]);

        $this->setReference(LoadProductDefaultAttributeFamilyData::DEFAULT_FAMILY_CODE, $defaultFamily);

        $attributeGroup = $defaultFamily->getAttributeGroup(LoadProductDefaultAttributeFamilyData::GENERAL_GROUP_CODE);

        $configManager = $this->getConfigManager();
        $variantField = $configManager->getConfigFieldModel(Product::class, 'multienum_field');

        $attributeGroupRelation = new AttributeGroupRelation();
        $attributeGroupRelation->setEntityConfigFieldId($variantField->getId());
        $attributeGroup->addAttributeRelation($attributeGroupRelation);

        $manager->persist($defaultFamily);
        $manager->flush();
    }
}
