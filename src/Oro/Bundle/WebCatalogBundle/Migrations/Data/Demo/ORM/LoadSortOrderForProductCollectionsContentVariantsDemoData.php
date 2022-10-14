<?php

namespace Oro\Bundle\WebCatalogBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ProductBundle\Entity\CollectionSortOrder;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Migrations\Data\Demo\ORM\LoadProductDemoData;
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
            LoadProductDemoData::class,
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
            ->findBy(['sku' => array_column($sortOrders, 'productSku')]);
        $segments = $manager
            ->getRepository(Segment::class)
            ->findBy(['name' => array_column($sortOrders, 'segmentName')]);

        foreach ($sortOrders as $sortOrder) {
            $collectionSortOrder = new CollectionSortOrder();
            foreach ($segments as $segment) {
                if ($segment->getName() === $sortOrder['segmentName']) {
                    $collectionSortOrder->setSegment($segment);
                }
            }
            foreach ($products as $product) {
                if ($product->getSku() === $sortOrder['productSku']) {
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
                'segmentName' => 'New Arrivals / Lighting Products',
                'productSku' => '2CF67',
                'sortOrder' => 1,
            ],
            [
                'segmentName' => 'New Arrivals / Lighting Products',
                'productSku' => '2JD90',
                'sortOrder' => 0.2,
            ],
            [
                'segmentName' => 'New Arrivals / Architectural Floodlighting',
                'productSku' => '4HJ92',
                'sortOrder' => 0,
            ]
        ];
    }
}
