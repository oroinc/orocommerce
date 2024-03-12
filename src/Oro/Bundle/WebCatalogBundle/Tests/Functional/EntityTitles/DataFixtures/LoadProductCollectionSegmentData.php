<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Functional\EntityTitles\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Entity\SegmentSnapshot;
use Oro\Bundle\SegmentBundle\Entity\SegmentType;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;

class LoadProductCollectionSegmentData extends AbstractFixture implements DependentFixtureInterface
{
    public const PRODUCT_COLLECTION_SEGMENT_1 = 'product-collection-segment-1';

    private array $segments = [
        [
            'name' => self::PRODUCT_COLLECTION_SEGMENT_1,
            'definition' => '',
            'type' => SegmentType::TYPE_DYNAMIC,
            'entity' => Product::class,
            'snapshotProducts' => [
                LoadProductData::PRODUCT_3,
                LoadProductData::PRODUCT_5
            ]
        ]
    ];

    /**
     * {@inheritDoc}
     */
    public function getDependencies(): array
    {
        return [LoadProductData::class, LoadOrganization::class];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager): void
    {
        /** @var Organization $organization */
        $organization = $this->getReference(LoadOrganization::ORGANIZATION);
        $owner = $organization->getBusinessUnits()->first();
        foreach ($this->segments as $item) {
            $segment = new Segment();
            $segment->setName($item['name']);
            $segment->setOrganization($organization);
            $segment->setOwner($owner);
            $segment->setDefinition($item['definition']);
            $segment->setType($manager->getRepository(SegmentType::class)->findOneBy(['name' => $item['type']]));
            $segment->setEntity($item['entity']);
            $this->setReference($item['name'], $segment);
            $manager->persist($segment);

            foreach ($item['snapshotProducts'] as $snapshotProduct) {
                /** @var Product $product */
                $product = $this->getReference($snapshotProduct);
                $segmentSnapshot = new SegmentSnapshot($segment);
                $segmentSnapshot->setIntegerEntityId($product->getId());
                $manager->persist($segmentSnapshot);
            }
        }
        $manager->flush();
    }
}
