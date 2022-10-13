<?php

namespace Oro\Bundle\WebCatalogBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ProductBundle\Entity\CollectionSortOrder;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SegmentBundle\Entity\Segment;

class LoadSortOrderForProductCollectionsContentVariantsDemoData extends AbstractFixture implements
    DependentFixtureInterface
{
    /**
     * {@inheritDoc}
     */
    public function getDependencies()
    {
        return [
            LoadSegmentsForWebCatalogDemoData::class,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager): void
    {
        $sortOrders = $this->getCollectionSortOrders();
        $products = $manager
            ->getRepository(Product::class)
            ->findBy(['id' => array_column($sortOrders, 'productId')]);
        $segments = $manager
            ->getRepository(Segment::class)
            ->findBy(['id' => array_column($sortOrders, 'segmentId')]);

        foreach ($sortOrders as $sortOrder) {
            $collectionSortOrder = new CollectionSortOrder();
            foreach ($segments as $segment) {
                if ($segment->getId() === $sortOrder['segmentId']) {
                    $collectionSortOrder->setSegment($segment);
                }
            }
            foreach ($products as $product) {
                if ($product->getId() === $sortOrder['productId']) {
                    $collectionSortOrder->setProduct($product);
                }
            }
            $collectionSortOrder->setSortOrder($sortOrder['sortOrder']);
            $manager->persist($collectionSortOrder);
        }

        $manager->flush();
    }

    /**
     * @return array
     */
    private function getCollectionSortOrders(): array
    {
        return [
            [
                'segmentId' => 4,
                'productId' => 6,
                'sortOrder' => 1,
            ],
            [
                'segmentId' => 4,
                'productId' => 9,
                'sortOrder' => 0.2,
            ],
            [
                'segmentId' => 5,
                'productId' => 24,
                'sortOrder' => 0,
            ]
        ];
    }
}
