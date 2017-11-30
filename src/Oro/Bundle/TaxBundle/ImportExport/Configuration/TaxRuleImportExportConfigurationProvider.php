<?php

namespace Oro\Bundle\TaxBundle\ImportExport\Configuration;

use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfiguration;
use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfigurationInterface;
use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfigurationProviderInterface;
use Oro\Bundle\TaxBundle\Entity\TaxRule;

class TaxRuleImportExportConfigurationProvider implements ImportExportConfigurationProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function get(): ImportExportConfigurationInterface
    {
        return new ImportExportConfiguration([
            ImportExportConfiguration::FIELD_ENTITY_CLASS => TaxRule::class,
            ImportExportConfiguration::FIELD_EXPORT_PROCESSOR_ALIAS => 'oro_tax_tax_rule',
            ImportExportConfiguration::FIELD_IMPORT_PROCESSOR_ALIAS => 'oro_tax_tax_rule',
        ]);
    }
}
