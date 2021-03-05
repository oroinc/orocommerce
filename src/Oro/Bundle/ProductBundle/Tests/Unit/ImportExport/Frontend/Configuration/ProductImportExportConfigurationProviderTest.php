<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\ImportExport\Frontend\Configuration;

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
            ImportExportConfiguration::FIELD_EXPORT_JOB_NAME => 'export_frontend_product_data_filtered_to_csv',
            ImportExportConfiguration::FIELD_EXPORT_PROCESSOR_ALIAS => 'oro_product_frontend_product_listing'
        ]);

        $this->assertEquals($expected, $this->provider->get());
    }
}
