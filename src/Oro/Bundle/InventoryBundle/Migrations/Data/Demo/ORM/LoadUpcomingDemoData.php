<?php
declare(strict_types=1);

namespace Oro\Bundle\InventoryBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\Fallback\Provider\CategoryFallbackProvider;
use Oro\Bundle\CatalogBundle\Fallback\Provider\ParentCategoryFallbackProvider;
use Oro\Bundle\CatalogBundle\Migrations\Data\Demo\ORM\LoadProductCategoryDemoData;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\InventoryBundle\Provider\UpcomingProductProvider;
use Oro\Bundle\MigrationBundle\Fixture\AbstractEntityReferenceFixture;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Migrations\Data\Demo\ORM\LoadProductDemoData;

/**
 * Demo data for Upcoming status.
 */
class LoadUpcomingDemoData extends AbstractEntityReferenceFixture implements DependentFixtureInterface
{
    public function getDependencies(): array
    {
        return [
            LoadProductDemoData::class,
            LoadProductCategoryDemoData::class
        ];
    }

    public function load(ObjectManager $manager): void
    {
        $accessor = PropertyAccess::createPropertyAccessor();

        /** @var CategoryRepository $categoryRepository */
        $categoryRepository = $manager->getRepository(Category::class);

        /** @var Product[] $products */
        $products = $manager->getRepository(Product::class)->findAll();

        /** @var Category[] $categories */
        $categories = $categoryRepository->findAll();
        foreach ($categories as $category) {
            $this->addFallbacksToEntity($manager, ParentCategoryFallbackProvider::FALLBACK_ID, $category);
        }

        $outOfStock = LoadProductDemoData::getProductInventoryStatus($manager, Product::INVENTORY_STATUS_OUT_OF_STOCK);

        foreach ($products as $product) {
            if ($categoryRepository->findOneByProduct($product)) {
                // It is not always the case in real life, but for the demo purposes we will mark
                // all out-of-stock products as upcoming.
                if ($product->getInventoryStatus() === $outOfStock) {
                    $fallbackEntity = new EntityFieldFallbackValue();
                    $fallbackEntity->setScalarValue(1);
                    $manager->persist($fallbackEntity);
                    $accessor->setValue($product, UpcomingProductProvider::IS_UPCOMING, $fallbackEntity);
                } else {
                    $this->addFallbacksToEntity($manager, CategoryFallbackProvider::FALLBACK_ID, $product);
                }
            }
        }

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param $fallbackId
     * @param $entity
     * @return mixed
     */
    protected function addFallbacksToEntity(ObjectManager $manager, $fallbackId, $entity)
    {
        $accessor = PropertyAccess::createPropertyAccessor();
        $fallbackEntity = $this->createFallbackEntity($manager, $fallbackId);
        $accessor->setValue($entity, UpcomingProductProvider::IS_UPCOMING, $fallbackEntity);
        return $entity;
    }

    protected function createFallbackEntity(ObjectManager $manager, string $fallbackId): EntityFieldFallbackValue
    {
        $entityFallback = new EntityFieldFallbackValue();
        $entityFallback->setFallback($fallbackId);
        $manager->persist($entityFallback);

        return $entityFallback;
    }
}
