<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\EventListener;

use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData;
use Oro\Bundle\DataGridBundle\ImportExport\FilteredEntityReader;
use Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceAttributeProductPrices;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class ExportDatagridListenerTest extends WebTestCase
{
    /** @var FilteredEntityReader */
    private $filteredEntityReader;

    protected function setUp(): void
    {
        $this->initClient();

        $this->filteredEntityReader = $this->getContainer()->get('oro_datagrid.importexport.export_filtered_reader');
        $this->loadFixtures([LoadCategoryProductData::class, LoadPriceAttributeProductPrices::class]);
    }

    public function testFilteredEntityReaderEmptyOptions()
    {
        $ids = $this->filteredEntityReader->getIds(PriceAttributeProductPrice::class, []);

        $expected = [];

        for ($i = 1; $i <= 14; ++ $i) {
            $expected[] = $this->getReference(LoadPriceAttributeProductPrices::REFERENCE.$i)->getId();
        }

        sort($expected);
        sort($ids);

        self::assertEquals($expected, $ids);
    }

    public function testFilteredEntityReaderIncludeNotCategorized()
    {
        $ids = $this->filteredEntityReader->getIds(PriceAttributeProductPrice::class, [
            'includeSubcategories' => true,
            'includeNotCategorizedProducts' => true,
        ]);

        self::assertEmpty($ids);
    }

    public function testFilteredEntityReaderCategory()
    {
        $ids = $this->filteredEntityReader->getIds(PriceAttributeProductPrice::class, [
            'categoryId' => $this->getReference(LoadCategoryData::SECOND_LEVEL1)->getId(),
        ]);

        $expected = [
            $this->getReference(LoadPriceAttributeProductPrices::REFERENCE.'5')->getId(),
            $this->getReference(LoadPriceAttributeProductPrices::REFERENCE.'6')->getId(),
            $this->getReference(LoadPriceAttributeProductPrices::REFERENCE.'7')->getId(),
            $this->getReference(LoadPriceAttributeProductPrices::REFERENCE.'10')->getId(),
            $this->getReference(LoadPriceAttributeProductPrices::REFERENCE.'11')->getId(),
            $this->getReference(LoadPriceAttributeProductPrices::REFERENCE.'12')->getId(),
            $this->getReference(LoadPriceAttributeProductPrices::REFERENCE.'13')->getId(),
            $this->getReference(LoadPriceAttributeProductPrices::REFERENCE.'14')->getId(),
        ];

        sort($expected);
        sort($ids);

        self::assertEquals($expected, $ids);
    }

    public function testFilteredEntityReaderCategoryIncludeSubcategories()
    {
        $ids = $this->filteredEntityReader->getIds(PriceAttributeProductPrice::class, [
            'categoryId' => $this->getReference(LoadCategoryData::SECOND_LEVEL1)->getId(),
            'includeSubcategories' => true,
        ]);

        $expected = [
            $this->getReference(LoadPriceAttributeProductPrices::REFERENCE.'5')->getId(),
            $this->getReference(LoadPriceAttributeProductPrices::REFERENCE.'6')->getId(),
            $this->getReference(LoadPriceAttributeProductPrices::REFERENCE.'7')->getId(),
            $this->getReference(LoadPriceAttributeProductPrices::REFERENCE.'8')->getId(),
            $this->getReference(LoadPriceAttributeProductPrices::REFERENCE.'10')->getId(),
            $this->getReference(LoadPriceAttributeProductPrices::REFERENCE.'11')->getId(),
            $this->getReference(LoadPriceAttributeProductPrices::REFERENCE.'12')->getId(),
            $this->getReference(LoadPriceAttributeProductPrices::REFERENCE.'13')->getId(),
            $this->getReference(LoadPriceAttributeProductPrices::REFERENCE.'14')->getId(),
        ];

        sort($expected);
        sort($ids);

        self::assertEquals($expected, $ids);
    }
}
