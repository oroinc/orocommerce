<?php

namespace Oro\Bundle\InventoryBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\Fallback\Provider\CategoryFallbackProvider;
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
        return ['Oro\Bundle\CatalogBundle\Migrations\Data\Demo\ORM\LoadProductCategoryDemoData'];
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
            $minQuantity = new EntityFieldFallbackValue();
            $minQuantity->setFallback(SystemConfigFallbackProvider::FALLBACK_ID);
            $maxQuantity = new EntityFieldFallbackValue();
            $maxQuantity->setFallback(SystemConfigFallbackProvider::FALLBACK_ID);
            $category->setMinimumQuantityToOrder($minQuantity);
            $category->setMaximumQuantityToOrder($maxQuantity);
            $manager->persist($minQuantity);
            $manager->persist($maxQuantity);
        }

        /** @var Product[] $products */
        $products = $manager->getRepository('OroProductBundle:Product')->findAll();
        foreach ($products as $product) {
            $category = $categoryRepository->findOneByProduct($product);
            if ($category) {
                $minQuantity = new EntityFieldFallbackValue();
                $minQuantity->setFallback(CategoryFallbackProvider::FALLBACK_ID);
                $maxQuantity = new EntityFieldFallbackValue();
                $maxQuantity->setFallback(CategoryFallbackProvider::FALLBACK_ID);
                $product->setMinimumQuantityToOrder($minQuantity);
                $product->setMaximumQuantityToOrder($maxQuantity);
            } else {
                $minQuantity = new EntityFieldFallbackValue();
                $minQuantity->setFallback(SystemConfigFallbackProvider::FALLBACK_ID);
                $maxQuantity = new EntityFieldFallbackValue();
                $maxQuantity->setFallback(SystemConfigFallbackProvider::FALLBACK_ID);
                $product->setMinimumQuantityToOrder($minQuantity);
                $product->setMaximumQuantityToOrder($maxQuantity);
            }
            $manager->persist($minQuantity);
            $manager->persist($maxQuantity);
        }

        $manager->flush();
    }
}
