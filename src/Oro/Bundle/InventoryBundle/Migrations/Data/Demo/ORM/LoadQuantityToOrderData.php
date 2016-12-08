<?php

namespace Oro\Bundle\InventoryBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\Fallback\Provider\CategoryFallbackProvider;
use Oro\Bundle\CatalogBundle\Migrations\Data\Demo\ORM\LoadProductCategoryDemoData;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityBundle\Fallback\Provider\SystemConfigFallbackProvider;
use Oro\Bundle\MigrationBundle\Fixture\AbstractEntityReferenceFixture;
use Oro\Bundle\ProductBundle\Entity\Product;

class LoadQuantityToOrderData extends AbstractEntityReferenceFixture implements DependentFixtureInterface
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
        /** @var CategoryRepository $categoryRepository */
        $categoryRepository = $manager->getRepository('OroCatalogBundle:Category');

        /** @var Category[] $categories */
        $categories = $categoryRepository->findAll();
        foreach ($categories as $category) {
            $category->setMinimumQuantityToOrder(
                $this->createFallbackEntity($manager, SystemConfigFallbackProvider::FALLBACK_ID)
            );
            $category->setMaximumQuantityToOrder(
                $this->createFallbackEntity($manager, SystemConfigFallbackProvider::FALLBACK_ID)
            );
        }

        /** @var Product[] $products */
        $products = $manager->getRepository('OroProductBundle:Product')->findAll();
        foreach ($products as $product) {
            $category = $categoryRepository->findOneByProduct($product);
            if ($category) {
                $product->setMinimumQuantityToOrder(
                    $this->createFallbackEntity($manager, CategoryFallbackProvider::FALLBACK_ID)
                );
                $product->setMaximumQuantityToOrder(
                    $this->createFallbackEntity($manager, CategoryFallbackProvider::FALLBACK_ID)
                );
            } else {
                $product->setMinimumQuantityToOrder(
                    $this->createFallbackEntity($manager, SystemConfigFallbackProvider::FALLBACK_ID)
                );
                $product->setMaximumQuantityToOrder(
                    $this->createFallbackEntity($manager, SystemConfigFallbackProvider::FALLBACK_ID)
                );
            }
        }

        $manager->flush();
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
