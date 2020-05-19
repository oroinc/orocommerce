<?php

namespace Oro\Bundle\CatalogBundle\Tests\Functional\ImportExport;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\Tests\Functional\CatalogTrait;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Tests\Functional\ImportExport\AbstractImportExportTestCase;

/**
 * @dbIsolationPerTest
 */
class ImportExportTest extends AbstractImportExportTestCase
{
    use CatalogTrait;

    public function testImportWithCategory()
    {
        $this->markTestSkipped(
            'This test will be completely removed and replaced with a set of smaller functional tests (see BAP-13064)'
        );
        $strategy = 'oro_product_product.add_or_replace';
        $this->loadFixtures([LoadCategoryData::class]);

        $filePath = implode(DIRECTORY_SEPARATOR, [__DIR__, 'data', 'import.csv']);

        $this->validateImportFile($strategy, $filePath);
        $this->doImport($strategy);

        /** @var ProductRepository $productRepository */
        $productRepository = $this->getRepository(Product::class);
        $products = $productRepository->findAll();
        $this->assertCount(9, $products);

        /** @var Product $sku1Product */
        $sku1Product = $productRepository->findOneBySku('sku1');

        /** @var CategoryRepository $categoryRepository */
        $categoryRepository = $this->getRepository(Category::class);
        $categoryFirst = $this->findCategory(LoadCategoryData::FIRST_LEVEL);
        $categorySecond = $this->findCategory(LoadCategoryData::SECOND_LEVEL1);

        $categoryFirst->removeProduct($sku1Product);
        $categorySecond->addProduct($sku1Product);

        $categoryManager = $this->getManager(Category::class);
        $categoryManager->flush();
        $categoryManager->clear();

        $category = $categoryRepository->findOneByProduct($sku1Product);
        $this->assertInstanceOf(Category::class, $category);
        $this->assertEquals($categorySecond->getId(), $category->getId());

        // Reimport after change category
        $this->validateImportFile($strategy, $filePath);
        $this->doImport($strategy);

        $category = $categoryRepository->findOneByProduct($sku1Product);
        $this->assertInstanceOf(Category::class, $category);
        $this->assertEquals($categoryFirst->getId(), $category->getId());
    }

    public function testImportWithSameCategory()
    {
        $this->markTestSkipped(
            'This test will be completely removed and replaced with a set of smaller functional tests (see BAP-13064)'
        );
        $strategy = 'oro_product_product.add_or_replace';
        $this->loadFixtures([LoadCategoryData::class]);

        $filePath = implode(DIRECTORY_SEPARATOR, [__DIR__, 'data', 'import.csv']);

        $this->validateImportFile($strategy, $filePath);
        $this->doImport($strategy);

        /** @var ProductRepository $productRepository */
        $productRepository = $this->getRepository(Product::class);

        /** @var Product $sku1Product */
        $sku1Product = $productRepository->findOneBySku('sku1');

        /** @var Category $categoryFirst */
        $categoryFirst = $this->getReference(LoadCategoryData::FIRST_LEVEL);

        /** @var CategoryRepository $categoryRepository */
        $categoryRepository = $this->getRepository(Category::class);
        $category = $categoryRepository->findOneByProduct($sku1Product);
        $this->assertNotEmpty($categoryFirst);
        $this->assertNotEmpty($category);
        $this->assertEquals($categoryFirst->getId(), $category->getId());

        // Reimport after change category
        $this->validateImportFile($strategy, $filePath);
        $this->doImport($strategy);

        $category = $categoryRepository->findOneByProduct($sku1Product);
        $this->assertInstanceOf(Category::class, $category);
        $this->assertEquals($categoryFirst->getId(), $category->getId());
    }
}
