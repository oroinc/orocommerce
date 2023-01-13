<?php

namespace Oro\Bundle\ProductBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

/**
 * Update all products with default product family
 */
class LoadProductDefaultAttributeFamilyData extends AbstractFixture implements
    DependentFixtureInterface,
    ContainerAwareInterface
{
    use UserUtilityTrait;
    use MakeProductAttributesTrait;

    const DEFAULT_FAMILY_CODE = 'default_family';
    const GENERAL_GROUP_CODE = 'general';

    private static array $groups = [
        [
            'groupLabel' => 'General',
            'groupCode' => self::GENERAL_GROUP_CODE,
            'attributes' => [
                'sku',
                'names',
                'descriptions',
                'shortDescriptions',
                'featured',
                'newArrival',
                'brand'
            ],
            'groupVisibility' => true
        ],
        [
            'groupLabel' => 'Inventory',
            'groupCode' => 'inventory',
            'attributes' => [
                'inventory_status'
            ],
            'groupVisibility' => false
        ],
        [
            'groupLabel' => 'Images',
            'groupCode' => 'images',
            'attributes' => [
                'images'
            ],
            'groupVisibility' => true
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies(): array
    {
        return [
            LoadDefaultAttributesData::class
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager): void
    {
        $user = $this->getFirstUser($manager);
        $organization = $user->getOrganization();

        $attributeFamily = $manager->getRepository(AttributeFamily::class)
            ->findOneBy(['code' => self::DEFAULT_FAMILY_CODE, 'owner' => $organization]);

        if ($attributeFamily === null) {
            $attributeFamily = new AttributeFamily();
            $attributeFamily->setCode(self::DEFAULT_FAMILY_CODE);
            $attributeFamily->setEntityClass(Product::class);
            $attributeFamily->setDefaultLabel('Default');
            $attributeFamily->setOwner($organization);
        }

        $this->addGroupsWithAttributesToFamily(self::$groups, $attributeFamily, $manager);
        $this->setReference(static::DEFAULT_FAMILY_CODE, $attributeFamily);

        $queryBuilder = $manager
            ->getRepository(Product::class)
            ->createQueryBuilder('product');

        $queryBuilder
            ->update(Product::class, 'product')
            ->set('product.attributeFamily', ':attributeFamily')
            ->setParameter('attributeFamily', $attributeFamily)
            ->getQuery()
            ->execute();
    }
}
