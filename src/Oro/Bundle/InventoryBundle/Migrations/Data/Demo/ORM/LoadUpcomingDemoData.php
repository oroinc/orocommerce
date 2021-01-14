<?php

namespace Oro\Bundle\InventoryBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\Fallback\Provider\CategoryFallbackProvider;
use Oro\Bundle\CatalogBundle\Fallback\Provider\ParentCategoryFallbackProvider;
use Oro\Bundle\CatalogBundle\Migrations\Data\Demo\ORM\LoadProductCategoryDemoData;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\InventoryBundle\Provider\UpcomingProductProvider;
use Oro\Bundle\MigrationBundle\Fixture\AbstractEntityReferenceFixture;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * Demo data for Upcoming status.
 */
class LoadUpcomingDemoData extends AbstractEntityReferenceFixture implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [LoadProductCategoryDemoData::class];
    }

    /**
     * @inheritDoc
     */
    public function load(ObjectManager $manager)
    {
        $accessor = PropertyAccess::createPropertyAccessor();

        /** @var CategoryRepository $categoryRepository */
        $categoryRepository = $manager->getRepository('OroCatalogBundle:Category');

        /** @var Product[] $products */
        $products = $manager->getRepository('OroProductBundle:Product')->findAll();

        /** @var Category[] $categories */
        $categories = $categoryRepository->findAll();
        foreach ($categories as $category) {
            $this->addFallbacksToEntity($manager, ParentCategoryFallbackProvider::FALLBACK_ID, $category);
        }

        $i = 0;
        $proportion = floor(count($products) / 10);
        foreach ($products as $product) {
            if ($categoryRepository->findOneByProduct($product)) {
                // we are set data to every 10`th product from parent category
                if ($i++ % $proportion !== 0) {
                    $this->addFallbacksToEntity($manager, CategoryFallbackProvider::FALLBACK_ID, $product);
                } else {
                    $fallbackEntity = new EntityFieldFallbackValue();
                    $fallbackEntity->setScalarValue(1);
                    $manager->persist($fallbackEntity);
                    $accessor->setValue($product, UpcomingProductProvider::IS_UPCOMING, $fallbackEntity);
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

    /**
     * @param ObjectManager $manager
     * @param string $fallbackId
     * @return EntityFieldFallbackValue
     */
    protected function createFallbackEntity(ObjectManager $manager, $fallbackId)
    {
        $entityFallback = new EntityFieldFallbackValue();
        $entityFallback->setFallback($fallbackId);
        $manager->persist($entityFallback);

        return $entityFallback;
    }
}
