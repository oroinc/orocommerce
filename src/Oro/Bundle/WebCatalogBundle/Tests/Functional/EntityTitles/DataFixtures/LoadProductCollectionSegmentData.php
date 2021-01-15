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
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadProductCollectionSegmentData extends AbstractFixture implements
    ContainerAwareInterface,
    DependentFixtureInterface
{
    const PRODUCT_COLLECTION_SEGMENT_1 = 'product-collection-segment-1';

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var array
     */
    private $segments = [
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
            LoadProductData::class
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->segments as $item) {
            $organization = $manager->getRepository(Organization::class)->getFirst();
            $owner = $organization->getBusinessUnits()->first();

            $segment = new Segment();
            $segment->setName($item['name']);
            $segment->setOrganization($organization);
            $segment->setOwner($owner);
            $segment->setDefinition($item['definition']);
            $segment->setType($this->getSegmentTypeByName($item['type']));
            $segment->setEntity($item['entity']);
            $this->setReference($item['name'], $segment);

            $manager->persist($segment);

            foreach ($item['snapshotProducts'] as $snapshotProduct) {
                $product = $this->getReference($snapshotProduct);

                $segmentSnapshot = new SegmentSnapshot($segment);
                $segmentSnapshot->setIntegerEntityId($product->getId());

                $manager->persist($segmentSnapshot);
            }
        }

        $manager->flush();
    }

    /**
     * @param string $name
     * @return SegmentType
     */
    private function getSegmentTypeByName($name)
    {
        return $this->container->get('doctrine')
            ->getManagerForClass(SegmentType::class)
            ->getRepository(SegmentType::class)
            ->findOneBy(['name' => $name]);
    }
}
