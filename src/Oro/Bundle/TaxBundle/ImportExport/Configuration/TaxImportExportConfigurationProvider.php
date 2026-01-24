<?php

namespace Oro\Bundle\TaxBundle\ImportExport\Configuration;

use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfiguration;
use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfigurationInterface;
use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfigurationProviderInterface;
use Oro\Bundle\TaxBundle\Entity\Tax;

/**
 * Provides import/export configuration for Tax entities.
 *
 * This configuration provider defines how tax rate entities should be imported from and exported
 * to external data sources. It specifies the entity class, processor aliases, and other settings
 * required for the import/export functionality to work with tax rates.
 */
class TaxImportExportConfigurationProvider implements ImportExportConfigurationProviderInterface
{
    #[\Override]
    public function get(): ImportExportConfigurationInterface
    {
        return new ImportExportConfiguration([
            ImportExportConfiguration::FIELD_ENTITY_CLASS => Tax::class,
            ImportExportConfiguration::FIELD_EXPORT_PROCESSOR_ALIAS => 'oro_tax_tax',
            ImportExportConfiguration::FIELD_IMPORT_PROCESSOR_ALIAS => 'oro_tax_tax',
        ]);
    }
}
