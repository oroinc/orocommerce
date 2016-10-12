<?php

namespace Oro\Bundle\InventoryBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\Fallback\Provider\CategoryFallbackProvider;
use Oro\Bundle\CatalogBundle\Fallback\Provider\ParentCategoryFallbackProvider;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityBundle\Fallback\Provider\SystemConfigFallbackProvider;
use Oro\Bundle\MigrationBundle\Fixture\AbstractEntityReferenceFixture;
use Oro\Bundle\ProductBundle\Entity\Product;

class LoadManagedInventoryData extends AbstractEntityReferenceFixture implements DependentFixtureInterface
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
            if ($category->getParentCategory()) {
                $value = new EntityFieldFallbackValue();
                $value->setFallback(ParentCategoryFallbackProvider::FALLBACK_ID);
                $category->setManageInventory($value);
            } else {
                $value = new EntityFieldFallbackValue();
                $value->setFallback(SystemConfigFallbackProvider::FALLBACK_ID);
                $category->setManageInventory($value);
            }
            $manager->persist($value);
        }

        /** @var Product[] $products */
        $products = $manager->getRepository('OroProductBundle:Product')->findAll();
        foreach ($products as $product) {
            $category = $categoryRepository->findOneByProduct($product);
            if ($category) {
                $value = new EntityFieldFallbackValue();
                $value->setFallback(CategoryFallbackProvider::FALLBACK_ID);
                $product->setManageInventory($value);
            } else {
                $value = new EntityFieldFallbackValue();
                $value->setFallback(SystemConfigFallbackProvider::FALLBACK_ID);
                $product->setManageInventory($value);
            }
            $manager->persist($value);
        }

        $manager->flush();
    }
}
