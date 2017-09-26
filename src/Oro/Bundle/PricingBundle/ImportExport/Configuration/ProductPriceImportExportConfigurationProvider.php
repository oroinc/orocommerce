<?php

namespace Oro\Bundle\PricingBundle\ImportExport\Configuration;

use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfiguration;
use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfigurationInterface;
use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfigurationProviderInterface;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;

class ProductPriceImportExportConfigurationProvider implements ImportExportConfigurationProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function get(): ImportExportConfigurationInterface
    {
        return new ImportExportConfiguration([
            ImportExportConfiguration::FIELD_ENTITY_CLASS => ProductPrice::class,
            ImportExportConfiguration::FIELD_EXPORT_JOB_NAME => 'price_list_product_prices_export_to_csv',
            ImportExportConfiguration::FIELD_EXPORT_PROCESSOR_ALIAS => 'oro_pricing_product_price',
            ImportExportConfiguration::FIELD_EXPORT_TEMPLATE_PROCESSOR_ALIAS => 'oro_pricing_product_price',
            ImportExportConfiguration::FIELD_IMPORT_PROCESSOR_ALIAS => 'oro_pricing_product_price.add_or_replace',
            ImportExportConfiguration::FIELD_IMPORT_JOB_NAME => 'price_list_product_prices_entity_import_from_csv',
        ]);
    }
}
