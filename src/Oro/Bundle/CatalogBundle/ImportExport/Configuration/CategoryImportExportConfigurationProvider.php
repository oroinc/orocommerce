<?php

namespace Oro\Bundle\CatalogBundle\ImportExport\Configuration;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfiguration;
use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfigurationInterface;
use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfigurationProviderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Import/export configuration provider for Category.
 */
class CategoryImportExportConfigurationProvider implements ImportExportConfigurationProviderInterface
{
    /** @var TranslatorInterface */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritDoc}
     */
    public function get(): ImportExportConfigurationInterface
    {
        return new ImportExportConfiguration(
            [
                ImportExportConfiguration::FIELD_ENTITY_CLASS => Category::class,
                ImportExportConfiguration::FIELD_EXPORT_PROCESSOR_ALIAS => 'oro_category',
                ImportExportConfiguration::FIELD_EXPORT_TEMPLATE_PROCESSOR_ALIAS => 'oro_category',
                ImportExportConfiguration::FIELD_IMPORT_PROCESSOR_ALIAS => 'oro_category.add_or_replace',
                ImportExportConfiguration::FIELD_IMPORT_JOB_NAME => 'category_import_from_csv',
                ImportExportConfiguration::FIELD_EXPORT_BUTTON_LABEL =>
                    $this->translator->trans('oro.catalog.category.importexport.export.label'),
            ]
        );
    }
}
