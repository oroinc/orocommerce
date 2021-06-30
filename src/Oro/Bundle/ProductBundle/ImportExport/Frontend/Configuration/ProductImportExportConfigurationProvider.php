<?php

namespace Oro\Bundle\ProductBundle\ImportExport\Frontend\Configuration;

use Oro\Bundle\DataGridBundle\ImportExport\FilteredEntityReader;
use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfiguration;
use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfigurationInterface;
use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfigurationProviderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * The configuration allows a customer on the Commerce storefront to load data from a product listing page.
 */
class ProductImportExportConfigurationProvider implements ImportExportConfigurationProviderInterface
{
    public const EXPORT_PROCESSOR_ALIAS = 'oro_product_frontend_product_listing';

    public function get(): ImportExportConfigurationInterface
    {
        return new ImportExportConfiguration([
            ImportExportConfiguration::FIELD_ENTITY_CLASS => Product::class,
            ImportExportConfiguration::FIELD_EXPORT_JOB_NAME => 'filtered_frontend_product_export_to_csv',
            ImportExportConfiguration::FIELD_EXPORT_PROCESSOR_ALIAS => self::EXPORT_PROCESSOR_ALIAS,
            ImportExportConfiguration::FIELD_ROUTE_OPTIONS => [
                FilteredEntityReader::FILTERED_RESULTS_GRID => 'frontend-product-search-grid'
            ]
        ]);
    }
}
