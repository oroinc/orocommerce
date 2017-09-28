<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\ImportExport\Configuration;

use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfiguration;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\ImportExport\Configuration\ProductPriceImportExportConfigurationProvider;
use PHPUnit\Framework\TestCase;

class ProductPriceImportExportConfigurationProviderTest extends TestCase
{
    public function testGet()
    {
        static::assertEquals(
            new ImportExportConfiguration([
                ImportExportConfiguration::FIELD_ENTITY_CLASS => ProductPrice::class,
                ImportExportConfiguration::FIELD_EXPORT_JOB_NAME => 'price_list_product_prices_export_to_csv',
                ImportExportConfiguration::FIELD_EXPORT_PROCESSOR_ALIAS => 'oro_pricing_product_price',
                ImportExportConfiguration::FIELD_EXPORT_TEMPLATE_PROCESSOR_ALIAS => 'oro_pricing_product_price',
                ImportExportConfiguration::FIELD_IMPORT_PROCESSOR_ALIAS => 'oro_pricing_product_price.add_or_replace',
                ImportExportConfiguration::FIELD_IMPORT_JOB_NAME => 'price_list_product_prices_entity_import_from_csv',
            ]),
            (new ProductPriceImportExportConfigurationProvider())->get()
        );
    }
}
