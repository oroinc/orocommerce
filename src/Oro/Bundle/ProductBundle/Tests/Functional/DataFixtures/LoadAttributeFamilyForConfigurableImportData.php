<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroup;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Tests\Functional\DataFixtures\LoadAttributeData;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class LoadAttributeFamilyForConfigurableImportData extends AbstractFixture implements
    DependentFixtureInterface,
    ContainerAwareInterface
{
    use ContainerAwareTrait;

    public const ATTRIBUTE_FAMILY_1 = 'attribute_family_1';

    private array $families = [
        self::ATTRIBUTE_FAMILY_1 => [
            'general',
            'inventory',
            LoadAttributeGroupForConfigurableImportData::CONFIGURABLE_ATTRIBUTE_GROUP_1,
        ],
    ];

    #[\Override]
    public function getDependencies(): array
    {
        return [LoadAttributeGroupForConfigurableImportData::class, LoadOrganization::class];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $configManager = $this->container->get('oro_entity_config.config_manager');
        /** @var EntityConfigModel $entityConfigModel */
        $entityConfigModel = $configManager->getConfigEntityModel(LoadAttributeData::ENTITY_CONFIG_MODEL);
        foreach ($this->families as $familyName => $groupNames) {
            $family = new AttributeFamily();
            $family->setDefaultLabel($familyName);
            $family->setOwner($this->getReference(LoadOrganization::ORGANIZATION));
            $family->setCode($familyName);
            $family->setEntityClass($entityConfigModel->getClassName());
            foreach ($groupNames as $groupName) {
                if ($this->hasReference($groupName)) {
                    /** @var AttributeGroup $group */
                    $group = $this->getReference($groupName);
                } else {
                    $group = $manager->getRepository(AttributeGroup::class)->findOneByCode($groupName);
                }
                $group->setAttributeFamily($family);
                $manager->persist($group);
                $family->addAttributeGroup($group);
            }
            $this->setReference($familyName, $family);
            $manager->persist($family);
        }
        $manager->flush();
    }
}
