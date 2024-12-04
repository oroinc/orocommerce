<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroup;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroupRelation;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class LoadAttributeGroupForConfigurableImportData extends AbstractFixture implements
    DependentFixtureInterface,
    ContainerAwareInterface
{
    use ContainerAwareTrait;

    const CONFIGURABLE_ATTRIBUTE_GROUP_1 = 'configurable_attribute_group_1';
    const BOOL_CONFIGURABLE_ATTRIBUTE = 'testAttrBoolean';

    protected array $groups = [
        'configurable' => [
            self::CONFIGURABLE_ATTRIBUTE_GROUP_1 => [
                self::BOOL_CONFIGURABLE_ATTRIBUTE,
            ],
        ],
    ];

    #[\Override]
    public function getDependencies()
    {
        return [
            LoadAttributeForConfigurableImportData::class,
        ];
    }

    #[\Override]
    public function load(ObjectManager $manager)
    {
        $configManager = $this->container->get('oro_entity_config.config_manager');
        foreach ($this->groups as $type => $groups) {
            foreach ($groups as $groupName => $attributes) {
                $group = new AttributeGroup();
                $group->setDefaultLabel($groupName);

                foreach ($attributes as $attributeName) {
                    $relation = new AttributeGroupRelation();
                    $relation->setAttributeGroup($group);
                    $relation->setEntityConfigFieldId(
                        LoadAttributeForConfigurableImportData::getAttribute($configManager, $attributeName)->getId()
                    );
                    $manager->persist($relation);
                    $group->addAttributeRelation($relation);
                }

                $this->setReference($groupName, $group);
                $manager->persist($group);
            }
        }
        $manager->flush();
    }
}
