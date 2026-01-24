<?php

namespace Oro\Bundle\TaxBundle\ImportExport\Configuration;

use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfiguration;
use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfigurationInterface;
use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfigurationProviderInterface;
use Oro\Bundle\TaxBundle\Entity\TaxRule;

/**
 * Provides import/export configuration for TaxRule entities.
 *
 * This configuration provider defines how tax rule entities should be imported from and exported to
 * external data sources. It specifies the entity class, processor aliases, and other settings required
 * for the import/export functionality to work with tax rules, which define the relationships
 * between customer tax codes, product tax codes, tax rates, and jurisdictions.
 */
class TaxRuleImportExportConfigurationProvider implements ImportExportConfigurationProviderInterface
{
    #[\Override]
    public function get(): ImportExportConfigurationInterface
    {
        return new ImportExportConfiguration([
            ImportExportConfiguration::FIELD_ENTITY_CLASS => TaxRule::class,
            ImportExportConfiguration::FIELD_EXPORT_PROCESSOR_ALIAS => 'oro_tax_tax_rule',
            ImportExportConfiguration::FIELD_IMPORT_PROCESSOR_ALIAS => 'oro_tax_tax_rule',
        ]);
    }
}
