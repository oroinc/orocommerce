<?php

namespace Oro\Bundle\WebsiteSearchBundle\ImportExport\Configuration;

use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfiguration;
use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfigurationInterface;
use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfigurationProviderInterface;
use Oro\Bundle\WebsiteSearchBundle\SearchResult\Entity\SearchResultHistory;

/**
 * Import-Export configuration provider for SearchResult.
 */
class SearchResultImportExportConfigurationProvider implements ImportExportConfigurationProviderInterface
{
    public function get(): ImportExportConfigurationInterface
    {
        return new ImportExportConfiguration([
            ImportExportConfiguration::FIELD_ENTITY_CLASS => SearchResultHistory::class,
            ImportExportConfiguration::FIELD_EXPORT_PROCESSOR_ALIAS => 'oro_website_search_result_history'
        ]);
    }
}
