<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroup;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroupRelation;
use Oro\Bundle\EntityExtendBundle\Entity\Repository\EnumValueRepository;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Migrations\Data\ORM\LoadProductDefaultAttributeFamilyData;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class LoadProductEnumAttributes extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @var array
     */
    private $attributes = [
        'testAttrEnum',
        'testAttrMultiEnum',
        'testAttrManyToOne',
        'testAttrBoolean',
        'type_contact',
        'contact_type',
        'contact',
    ];

    public function load(ObjectManager $manager)
    {
        $this->addProductAttributesToFamily($manager);

        $this->createEnumOptions(
            $manager,
            'test_prod_attr_m_enum',
            [
                'Multi Enum First Option' => true,
                'Multi Enum Second Option' => false,
                'Multi Enum Third Option' => false,
            ]
        );
        $this->createEnumOptions(
            $manager,
            'test_prod_attr_enum',
            [
                'Enum First Option' => true,
                'Enum Second Option' => false,
                'Enum Third Option' => false,
            ]
        );

        $manager->flush();
    }

    private function createEnumOptions(ObjectManager $manager, $enumCode, array $data)
    {
        $className = ExtendHelper::buildEnumValueClassName($enumCode);

        /**
         * @var EnumValueRepository $enumValueRepository
         */
        $enumValueRepository = $manager->getRepository($className);

        $priority = 1;
        foreach ($data as $name => $isDefault) {
            $enumOption = $enumValueRepository->createEnumValue($name, $priority++, $isDefault);
            $manager->persist($enumOption);
        }
    }

    private function addProductAttributesToFamily(ObjectManager $manager)
    {
        $attributeFamily = $manager->getRepository(AttributeFamily::class)
            ->findOneBy(['code' => LoadProductDefaultAttributeFamilyData::DEFAULT_FAMILY_CODE]);

        foreach ($this->attributes as $attributeName) {
            $attribute = $this->container->get('oro_entity_config.config_manager')
                ->getConfigFieldModel(Product::class, $attributeName);

            $group = $manager->getRepository(AttributeGroup::class)
                ->findOneBy([
                    'code' => LoadProductDefaultAttributeFamilyData::GENERAL_GROUP_CODE,
                    'attributeFamily' => $attributeFamily,
                ]);

            $relation = new AttributeGroupRelation();
            $relation->setAttributeGroup($group);
            $relation->setEntityConfigFieldId($attribute->getId());
            $manager->persist($relation);
        }
    }
}
