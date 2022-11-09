<?php

namespace Oro\Bundle\WebCatalogBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ProductBundle\Entity\CollectionSortOrder;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Migrations\Data\Demo\ORM\LoadProductDemoData;
use Oro\Bundle\SegmentBundle\Entity\Segment;

/**
 * Loads sort order demo data for ProductCollection ContentVariants in WebCatalog
 */
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
                'sortOrder' => 6,
            ],
            [
                'segmentName' => 'New Arrivals / Lighting Products',
                'productSku' => '2JD90',
                'sortOrder' => 9,
            ],
            [
                'segmentName' => 'New Arrivals / Lighting Products',
                'productSku' => '3UK92',
                'sortOrder' => 21,
            ],
            [
                'segmentName' => 'New Arrivals / Architectural Floodlighting',
                'productSku' => '3TU20',
                'sortOrder' => 20,
            ],
            [
                'segmentName' => 'New Arrivals / Architectural Floodlighting',
                'productSku' => '4HJ92',
                'sortOrder' => 24,
            ],
            [
                'segmentName' => 'New Arrivals / Architectural Floodlighting',
                'productSku' => '5TU10',
                'sortOrder' => 34,
            ],
            [
                'segmentName' => 'New Arrivals / Office Furniture',
                'productSku' => '3ET67',
                'sortOrder' => 15,
            ],
            [
                'segmentName' => 'New Arrivals / Office Furniture',
                'productSku' => '4KL66',
                'sortOrder' => 25,
            ],
            [
                'segmentName' => 'New Arrivals / Office Furniture',
                'productSku' => '6GH85',
                'sortOrder' => 38,
            ],
            [
                'segmentName' => 'New Arrivals / Office Furniture',
                'productSku' => '6PM40',
                'sortOrder' => 40,
            ],
            [
                'segmentName' => 'New Arrivals / Retail Supplies',
                'productSku' => '1AB92',
                'sortOrder' => 2,
            ],
            [
                'segmentName' => 'New Arrivals / Retail Supplies',
                'productSku' => '2LM04',
                'sortOrder' => 11,
            ],
            [
                'segmentName' => 'New Arrivals / Retail Supplies',
                'productSku' => '4PJ19',
                'sortOrder' => 26,
            ],
            [
                'segmentName' => 'New Arrivals / Retail Supplies',
                'productSku' => '7TY55',
                'sortOrder' => 48,
            ],
        ];
    }
}
