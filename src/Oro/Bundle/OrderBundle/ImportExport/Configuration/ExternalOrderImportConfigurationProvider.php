<?php

namespace Oro\Bundle\OrderBundle\ImportExport\Configuration;

use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfiguration;
use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfigurationInterface;
use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfigurationProviderInterface;
use Oro\Bundle\OrderBundle\Entity\Order;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Providers the configuration for the import of external orders.
 */
class ExternalOrderImportConfigurationProvider implements ImportExportConfigurationProviderInterface
{
    public function __construct(
        private TranslatorInterface $translator
    ) {
    }

    #[\Override]
    public function get(): ImportExportConfigurationInterface
    {
        return new ImportExportConfiguration([
            ImportExportConfiguration::FIELD_ENTITY_CLASS => Order::class,
            ImportExportConfiguration::FIELD_IMPORT_JOB_NAME => 'external_order_import_from_json',
            ImportExportConfiguration::FIELD_IMPORT_VALIDATION_JOB_NAME => 'external_order_import_validation_from_json',
            ImportExportConfiguration::FIELD_IMPORT_PROCESSOR_ALIAS => 'external_order_import.add',
            ImportExportConfiguration::FIELD_IMPORT_COLUMNS_NOTICE =>
                $this->translator->trans('oro.order.external_import.columns_notice'),
            ImportExportConfiguration::FIELD_IMPORT_ENTITY_LABEL =>
                $this->translator->trans('oro.order.external_import.button.label'),
            ImportExportConfiguration::FIELD_IMPORT_FORM_FILE_ACCEPT_ATTRIBUTE => '.json,application/json',
            ImportExportConfiguration::FIELD_IMPORT_FORM_FILE_MIME_TYPES => ['application/json'],
            ImportExportConfiguration::FIELD_EXPORT_TEMPLATE_JOB_NAME => 'entity_export_template_to_json',
            ImportExportConfiguration::FIELD_EXPORT_TEMPLATE_PROCESSOR_ALIAS => 'external_order_import',
            ImportExportConfiguration::FIELD_FEATURE_NAME => 'external_order_import'
        ]);
    }
}
