<?php
declare(strict_types=1);

namespace Oro\Bundle\WebCatalogBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CatalogBundle\Migrations\Data\Demo\ORM\LoadCategoryBasedSegmentsDemoData;
use Oro\Bundle\ProductBundle\Entity\CollectionSortOrder;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Migrations\Data\Demo\ORM\LoadProductDemoData;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Loads sort order demo data for ProductCollection ContentVariants in WebCatalog
 */
class LoadSortOrderForProductCollectionsContentVariantsDemoData extends AbstractFixture implements
    ContainerAwareInterface,
    DependentFixtureInterface
{
    use ContainerAwareTrait;

    public function getDependencies(): array
    {
        return [
            LoadProductDemoData::class,
            LoadCategoryBasedSegmentsDemoData::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        // Set sort order for category-based product segment the same as for products in the original category

        $segmentsByCategoryName = [];
        $allSegments = $manager->getRepository(Segment::class)->findByNameStartsWith(
            LoadCategoryBasedSegmentsDemoData::NEW_ARRIVALS_PREFIX,
            Product::class
        );
        foreach ($allSegments as $segment) {
            $categoryName = \str_replace(
                LoadCategoryBasedSegmentsDemoData::NEW_ARRIVALS_PREFIX,
                '',
                $segment->getName()
            );
            $segmentsByCategoryName[$categoryName] = $segment;
        }

        $productData = $this->getAllProductData();
        foreach ($productData as $row) {
            if ($row['new_arrival'] && $segmentsByCategoryName[$row['category']]) {
                $product = $manager->getRepository(Product::class)->findOneBySku($row['sku']);
                if ($product) {
                    $collectionSortOrder = (new CollectionSortOrder())
                        ->setSegment($segmentsByCategoryName[$row['category']])
                        ->setProduct($product)
                        ->setSortOrder((float)$row['category_sort_order'])
                    ;
                    $manager->persist($collectionSortOrder);
                }
            }
        }

        $manager->flush();
    }

    private function getAllProductData(): array
    {
        $products = [];

        $locator = $this->container->get('file_locator');
        $filePath = $locator->locate('@OroProductBundle/Migrations/Data/Demo/ORM/data/products.csv');

        if (is_array($filePath)) {
            $filePath = current($filePath);
        }

        $handler = fopen($filePath, 'r');
        $headers = fgetcsv($handler, 1000, ',');

        while (($data = fgetcsv($handler, 1000, ',')) !== false) {
            $row = array_combine($headers, array_values($data));
            $products[] = $row;
        }

        fclose($handler);

        return $products;
    }
}
