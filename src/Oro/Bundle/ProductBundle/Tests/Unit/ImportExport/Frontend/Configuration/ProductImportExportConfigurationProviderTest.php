<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\ImportExport\Frontend\Configuration;

use Oro\Bundle\DataGridBundle\ImportExport\FilteredEntityReader;
use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfiguration;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\ImportExport\Frontend\Configuration\ProductImportExportConfigurationProvider;
use PHPUnit\Framework\TestCase;

class ProductImportExportConfigurationProviderTest extends TestCase
{
    private ProductImportExportConfigurationProvider $provider;

    protected function setUp(): void
    {
        $this->provider = new ProductImportExportConfigurationProvider();
    }

    public function testGet()
    {
        $expected = new ImportExportConfiguration([
            ImportExportConfiguration::FIELD_ENTITY_CLASS => Product::class,
            ImportExportConfiguration::FIELD_EXPORT_JOB_NAME => 'filtered_frontend_product_export_to_csv',
            ImportExportConfiguration::FIELD_EXPORT_PROCESSOR_ALIAS => 'oro_product_frontend_product_listing',
            ImportExportConfiguration::FIELD_ROUTE_OPTIONS => [
                FilteredEntityReader::FILTERED_RESULTS_GRID => 'frontend-product-search-grid'
            ]
        ]);

        $this->assertEquals($expected, $this->provider->get());
    }
}
