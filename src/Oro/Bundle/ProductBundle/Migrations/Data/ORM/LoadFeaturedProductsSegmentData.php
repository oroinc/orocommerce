<?php

namespace Oro\Bundle\ProductBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Migrations\Data\ORM\LoadOrganizationAndBusinessUnitData;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Entity\SegmentType;
use Oro\Bundle\SegmentBundle\Migrations\Data\ORM\LoadSegmentTypes;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Loads featured products segments.
 */
class LoadFeaturedProductsSegmentData extends AbstractFixture implements
    DependentFixtureInterface,
    ContainerAwareInterface
{
    use ContainerAwareTrait;

    private const SEGMENT_RECORDS_LIMIT = 10;
    private const FEATURED_PRODUCTS_SEGMENT_NAME_PARAMETER_NAME = 'oro_product.segment.featured_products.name';

    #[\Override]
    public function getDependencies(): array
    {
        return [
            LoadSegmentTypes::class,
            LoadOrganizationAndBusinessUnitData::class
        ];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $segmentName = $this->container->getParameter(self::FEATURED_PRODUCTS_SEGMENT_NAME_PARAMETER_NAME);
        if ($this->isSegmentAlreadyExists($manager, $segmentName)) {
            return;
        }

        $segment = new Segment();
        $segment->setName($segmentName);
        $segment->setEntity(Product::class);
        $segment->setType($this->getSegmentType($manager, SegmentType::TYPE_DYNAMIC));
        $segment->setRecordsLimit(self::SEGMENT_RECORDS_LIMIT);

        $organization = $this->getOrganization($manager);
        $segment->setOrganization($organization);
        $segment->setOwner($organization->getBusinessUnits()->first());

        $segment->setDefinition(json_encode($this->getDefinition()));

        $manager->persist($segment);
        $manager->flush();
    }

    private function isSegmentAlreadyExists(ObjectManager $manager, string $name): bool
    {
        $segment = $manager->getRepository(Segment::class)->findOneBy(['name' => $name]);

        return (bool)$segment;
    }

    private function getSegmentType(ObjectManager $manager, string $name): SegmentType
    {
        return $manager->getRepository(SegmentType::class)->findOneBy(['name' => $name]);
    }

    private function getOrganization(ObjectManager $manager): Organization
    {
        return $manager->getRepository(Organization::class)->getFirst();
    }

    private function getDefinition(): array
    {
        return [
            'columns' => [
                [
                    'name'    => 'id',
                    'label'   => 'Id',
                    'sorting' => '',
                    'func'    => null,
                ],
                [
                    'name'    => 'updatedAt',
                    'label'   => 'Updated At',
                    'sorting' => 'DESC',
                    'func'    => null,
                ],
            ],
            'filters' => [
                [
                    'columnName' => 'featured',
                    'criterion' => [
                        'filter' => 'boolean',
                        'data' => [
                            'value' => 1
                        ]
                    ]
                ]
            ]
        ];
    }
}
