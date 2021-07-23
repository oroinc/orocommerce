<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Entity\SegmentType;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadProductCollectionSegmentWithManuallyAddedData extends AbstractFixture implements
    DependentFixtureInterface,
    ContainerAwareInterface
{
    const SEGMENT_WITH_FILTERS = 'product-collection-segment-with-filters';
    const SEGMENT_WITH_MANUALLY_ADDED = 'product-collection-segment-manually-added';
    const SEGMENT_WITH_MIXED = 'product-collection-segment-mixed';

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadProductData::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $product1 = $this->getReference(LoadProductData::PRODUCT_1);
        $product2 = $this->getReference(LoadProductData::PRODUCT_2);
        $segmentType = $manager->getRepository(SegmentType::class)->find(SegmentType::TYPE_DYNAMIC);
        $organization = $manager->getRepository(Organization::class)->getFirst();
        $owner = $organization->getBusinessUnits()->first();
        $converter = $this->container->get('oro_product.service.product_collection_definition_converter');

        $segmentWithFilters = $this->createSegment(
            $segmentType,
            self::SEGMENT_WITH_FILTERS,
            json_encode([
                'columns' => [],
                'filters' => [
                    [
                        [
                            'columnName' => 'id',
                            'criterion' => [
                                'filter' => 'number',
                                'data' => [
                                    'value' => implode(',', [$product1->getId(), $product2->getId()]),
                                    'type' => 9,
                                ],
                            ],
                        ],
                    ],
                ],
            ]),
            $owner,
            $organization
        );
        $segmentWithManuallyAdded = $this->createSegment(
            $segmentType,
            self::SEGMENT_WITH_MANUALLY_ADDED,
            $converter->putConditionsInDefinition(
                json_encode(['columns' => [], 'filters' => []]),
                null,
                implode(',', [$product1->getId(), $product2->getId()])
            ),
            $owner,
            $organization
        );
        $segmentWithMixed = $this->createSegment(
            $segmentType,
            self::SEGMENT_WITH_MIXED,
            $converter->putConditionsInDefinition(
                json_encode([
                    'columns' => [],
                    'filters' => [
                        [
                            [
                                'columnName' => 'id',
                                'criterion' => [
                                    'filter' => 'number',
                                    'data' => [
                                        'value' => $product1->getId(),
                                        'type' => 3,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]),
                null,
                (string) $product2->getId()
            ),
            $owner,
            $organization
        );

        $manager->persist($segmentWithFilters);
        $manager->persist($segmentWithManuallyAdded);
        $manager->persist($segmentWithMixed);
        $manager->flush();
    }

    private function createSegment(
        SegmentType $type,
        string $name,
        string $definition,
        BusinessUnit $owner,
        Organization $organization
    ): Segment {
        $segment = new Segment();
        $segment->setName($name);
        $segment->setEntity(Product::class);
        $segment->setType($type);
        $segment->setDefinition($definition);
        $segment->setOwner($owner);
        $segment->setOrganization($organization);
        $this->setReference($name, $segment);

        return $segment;
    }
}
