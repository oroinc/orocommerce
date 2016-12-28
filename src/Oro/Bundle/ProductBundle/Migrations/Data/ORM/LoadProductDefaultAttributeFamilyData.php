<?php

namespace Oro\Bundle\ProductBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroup;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroupRelation;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadRolesData;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class LoadProductDefaultAttributeFamilyData extends AbstractFixture implements
    DependentFixtureInterface,
    ContainerAwareInterface
{
    /**
     * @var string
     */
    const DEFAULT_FAMILY_CODE = 'default_family';

    use ContainerAwareTrait;

    /**
     * @var array
     */
    private static $groups = [
        [
            'groupLabel' => 'General',
            'groupCode' => 'general',
            'attributes' => [
                'sku',
                'names',
                'descriptions',
                'shortDescriptions',
            ],
        ],
        [
            'groupLabel' => 'Product Prices',
            'groupCode' => 'prices',
            'attributes' => [
                'productPriceAttributesPrices'
            ],
        ],
        [
            'groupLabel' => 'Inventory',
            'groupCode' => 'inventory',
            'attributes' => [
                'inventory_status'
            ],
        ],
        [
            'groupLabel' => 'SEO',
            'groupCode' => 'seo',
            'attributes' => [
                'metaKeywords',
                'metaDescriptions'
            ],
        ],
        [
            'groupLabel' => 'Images',
            'groupCode' => 'images',
            'attributes' => [
                'images'
            ],
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
        $configManager = $this->container->get('oro_entity_config.config_manager');
        $attributeFamily = new AttributeFamily();
        $attributeFamily->setCode(self::DEFAULT_FAMILY_CODE);
        $attributeFamily->setEntityClass(Product::class);
        $attributeFamily->setOwner($this->getUser($manager));
        $attributeFamily->setDefaultLabel('Default');

        foreach (self::$groups as $groupData) {
            $attributeGroup = new AttributeGroup();
            $attributeGroup->setDefaultLabel($groupData['groupLabel']);
            $attributeGroup->setCode($groupData['groupCode']);
            foreach ($groupData['attributes'] as $attribute) {
                $fieldConfigModel = $configManager->getConfigFieldModel(Product::class, $attribute);
                $attributeGroupRelation = new AttributeGroupRelation();
                $attributeGroupRelation->setEntityConfigFieldId($fieldConfigModel->getId());
                $attributeGroup->addAttributeRelation($attributeGroupRelation);
            }

            $attributeFamily->addAttributeGroup($attributeGroup);
        }

        $manager->persist($attributeFamily);
        $manager->flush();

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
