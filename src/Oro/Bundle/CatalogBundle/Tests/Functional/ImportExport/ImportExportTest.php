<?php

namespace Oro\Bundle\CatalogBundle\Tests\Functional\ImportExport;

use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\ImportExport\AbstractImportExportTest;

/**
 * @dbIsolationPerTest
 */
class ImportExportTest extends AbstractImportExportTest
{
    public function testImportWithCategory()
    {
        $strategy = 'oro_product_product.add_or_replace';
        $productClass = $this->getContainer()->getParameter('oro_product.entity.product.class');
        $categoryClass = $this->getContainer()->getParameter('oro_catalog.entity.category.class');
        $this->loadFixtures([LoadCategoryData::class]);

        $filePath = implode(DIRECTORY_SEPARATOR, [__DIR__, 'data', 'import.csv']);

        $this->validateImportFile($strategy, $filePath);
        $this->doImport($strategy);

        $productRepository = $this->getRepository($productClass);

        /** @var CategoryRepository $categoryRepository */
        $categoryRepository = $this->getRepository($categoryClass);
        $categoryManager = $this->getManager($categoryClass);

        $products = $productRepository->findAll();
        $this->assertCount(9, $products);

        /** @var Product $sku1Product */
        $sku1Product = $productRepository->findOneBy(['sku' => 'sku1']);

        $categoryFirst = $categoryRepository->findOneByDefaultTitle(LoadCategoryData::FIRST_LEVEL);
        $categorySecond = $categoryRepository->findOneByDefaultTitle(LoadCategoryData::SECOND_LEVEL1);

        $categoryFirst->removeProduct($sku1Product);
        $categorySecond->addProduct($sku1Product);

        $categoryManager->flush();
        $categoryManager->clear();

        // Reimport after change category
        $this->validateImportFile($strategy, $filePath);
        $this->doImport($strategy);

        /** @var Product $sku1Product */
        $sku1Product = $productRepository->findOneBy(['sku' => 'sku1']);
        $category = $categoryRepository->findOneByProduct($sku1Product);
        $this->assertEquals(LoadCategoryData::FIRST_LEVEL, $category->getDefaultTitle()->getString());
    }
}
