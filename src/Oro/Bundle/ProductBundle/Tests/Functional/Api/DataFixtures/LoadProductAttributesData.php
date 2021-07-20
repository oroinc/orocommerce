<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Api\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroup;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroupRelation;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductDefaultAttributeFamily;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\InitialFixtureInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Loads all product attributes from the database.
 */
class LoadProductAttributesData extends AbstractFixture implements
    DependentFixtureInterface,
    InitialFixtureInterface,
    ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadProductDefaultAttributeFamily::class
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $attributes = [
            '1'          => [
                'testAttrInvisible',
                'testAttrString',
                'testAttrBoolean',
                'testAttrFloat',
                'testAttrMoney',
                'testAttrDateTime'
            ],
            '2'          => [
                'testAttrMultiEnum',
                'testAttrManyToOne',
                'testToOneId',
                'testAttrManyToMany',
                'testToManyId'
            ],
            '_invisible' => [
                'testAttrInteger',
                'testAttrEnum'
            ]
        ];
        $attributeIds = $this->getAttributeIds(array_merge(...array_values($attributes)));

        /** @var AttributeFamily $defaultFamily */
        $defaultFamily = $this->getReference('default_product_family');

        foreach ($attributes as $groupSuffix => $attributeNames) {
            $group = $this->createAttributeGroup('Group ' . $groupSuffix, '_invisible' !== $groupSuffix);
            $group->setAttributeFamily($defaultFamily);
            foreach ($attributeNames as $attributeName) {
                $relation = new AttributeGroupRelation();
                $relation->setAttributeGroup($group);
                $relation->setEntityConfigFieldId($attributeIds[$attributeName]);
                $manager->persist($relation);
                $group->addAttributeRelation($relation);
            }
            $this->setReference('productAttributeGroup' . $groupSuffix, $group);
            $manager->persist($group);
        }

        $manager->flush();
    }

    /**
     * @param string[] $attributeNames
     *
     * @return array [attribute name => attribute id, ...]
     */
    private function getAttributeIds(array $attributeNames): array
    {
        $result = [];
        $configManager = $this->container->get('oro_entity_config.config_manager');
        foreach ($attributeNames as $attributeName) {
            $attributeId = $configManager->getConfigModelId(Product::class, $attributeName);
            if (null === $attributeId) {
                throw new \LogicException(sprintf('Cannot find ID for product attribute "%s".', $attributeName));
            }
            $result[$attributeName] = $attributeId;
        }

        return $result;
    }

    private function createAttributeGroup(string $label, bool $visible = true): AttributeGroup
    {
        $group = new AttributeGroup();
        $group->setIsVisible($visible);
        $group->setDefaultLabel($label);

        return $group;
    }
}
