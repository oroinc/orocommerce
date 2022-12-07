<?php

namespace Oro\Bundle\CatalogBundle\Tests\Functional\EventListener;

use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData;
use Oro\Bundle\DataGridBundle\ImportExport\FilteredEntityReader;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ExportDatagridListenerTest extends WebTestCase
{
    /** @var FilteredEntityReader */
    private $filteredEntityReader;

    protected function setUp(): void
    {
        $this->initClient();

        $this->filteredEntityReader = $this->getContainer()->get('oro_datagrid.importexport.export_filtered_reader');

        $this->loadFixtures([LoadCategoryProductData::class]);
    }

    public function testFilteredEntityReaderEmptyOptions()
    {
        $ids = $this->filteredEntityReader->getIds(Product::class, []);

        $expected = [
            $this->getReference(LoadProductData::PRODUCT_1)->getId(),
            $this->getReference(LoadProductData::PRODUCT_2)->getId(),
            $this->getReference(LoadProductData::PRODUCT_3)->getId(),
            $this->getReference(LoadProductData::PRODUCT_4)->getId(),
            $this->getReference(LoadProductData::PRODUCT_5)->getId(),
            $this->getReference(LoadProductData::PRODUCT_6)->getId(),
            $this->getReference(LoadProductData::PRODUCT_7)->getId(),
            $this->getReference(LoadProductData::PRODUCT_8)->getId(),
            $this->getReference(LoadProductData::PRODUCT_9)->getId(),
        ];

        sort($expected);
        sort($ids);

        self::assertEquals($expected, $ids);
    }

    public function testFilteredEntityReaderIncludeNotCategorized()
    {
        $ids = $this->filteredEntityReader->getIds(Product::class, [
            'includeSubcategories' => true,
            'includeNotCategorizedProducts' => true,
        ]);

        self::assertEquals(
            [
                $this->getReference(LoadProductData::PRODUCT_9)->getId(),
            ],
            $ids
        );
    }

    public function testFilteredEntityReaderCategory()
    {
        $ids = $this->filteredEntityReader->getIds(Product::class, [
            'categoryId' => $this->getReference(LoadCategoryData::FOURTH_LEVEL2)->getId(),
            'includeSubcategories' => true,
        ]);

        $expected = [
            $this->getReference(LoadProductData::PRODUCT_7)->getId(),
            $this->getReference(LoadProductData::PRODUCT_8)->getId(),
        ];

        sort($expected);
        sort($ids);

        self::assertEquals($expected, $ids);
    }

    public function testFilteredEntityReader()
    {
        $ids = $this->filteredEntityReader->getIds(Product::class, [
            'categoryId' => $this->getReference(LoadCategoryData::FOURTH_LEVEL2)->getId(),
            'includeSubcategories' => true,
            'includeNotCategorizedProducts' => true,
        ]);

        $expected = [
            $this->getReference(LoadProductData::PRODUCT_7)->getId(),
            $this->getReference(LoadProductData::PRODUCT_8)->getId(),
            $this->getReference(LoadProductData::PRODUCT_9)->getId(),
        ];

        sort($expected);
        sort($ids);

        self::assertEquals($expected, $ids);
    }
}
