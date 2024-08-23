<?php

namespace Oro\Bundle\ProductBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\LocaleBundle\Migrations\Data\ORM\LoadLocalizationData;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

/**
 * Updates the inventory status attribute configuration.
 */
class LoadDefaultAttributesData extends AbstractFixture implements
    ContainerAwareInterface,
    DependentFixtureInterface
{
    use MakeProductAttributesTrait;

    private array $fields = [
        'inventory_status' => ['filterable' => true,]
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies(): array
    {
        return [
            LoadLocalizationData::class
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager): void
    {
        $this->makeProductAttributes(
            $this->fields,
            ExtendScope::OWNER_SYSTEM,
            ['frontend' => ['is_displayable' => false]]
        );
    }
}
