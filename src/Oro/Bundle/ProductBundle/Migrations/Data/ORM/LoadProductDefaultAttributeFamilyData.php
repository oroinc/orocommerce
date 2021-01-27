<?php

namespace Oro\Bundle\ProductBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadRolesData;
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

    /**
     * @var array
     */
    private static $groups = [
        [
            'groupLabel' => 'General',
            'groupCode' => self::GENERAL_GROUP_CODE,
            'attributes' => [
                'sku',
                'names',
                'descriptions',
                'shortDescriptions',
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
    public function getDependencies()
    {
        return [
            LoadDefaultAttributesData::class
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $user = $this->getFirstUser($manager);
        $organization = $user->getOrganization();

        /** @var AttributeFamily $attributeFamily */
        $attributeFamily = $manager->getRepository(AttributeFamily::class)
            ->findOneBy(['owner' => $organization]);

        if ($attributeFamily === null) {
            $attributeFamily = new AttributeFamily();
            $attributeFamily->setCode(self::DEFAULT_FAMILY_CODE);
            $attributeFamily->setEntityClass(Product::class);
            $attributeFamily->setDefaultLabel('Default');
        } else {
            $groups = $attributeFamily->getAttributeGroups();
            foreach ($groups as $group) {
                $attributeFamily->removeAttributeGroup($group);
            }
        }
        $attributeFamily->setOwner($organization);

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

    /**
     * @param ObjectManager $manager
     *
     * @throws \RuntimeException
     *
     * @return User
     */
    protected function getUser(ObjectManager $manager)
    {
        $role = $manager->getRepository('OroUserBundle:Role')->findOneBy(['role' => LoadRolesData::ROLE_ADMINISTRATOR]);
        if (!$role) {
            throw new \RuntimeException(sprintf('%s role should exist.', LoadRolesData::ROLE_ADMINISTRATOR));
        }

        $user = $manager->getRepository('OroUserBundle:Role')->getFirstMatchedUser($role);
        if (!$user) {
            throw new \RuntimeException(
                sprintf('At least one user with role %s should exist.', LoadRolesData::ROLE_ADMINISTRATOR)
            );
        }

        return $user;
    }
}
