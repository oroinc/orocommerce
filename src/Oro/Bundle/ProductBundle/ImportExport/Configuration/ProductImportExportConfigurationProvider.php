<?php

namespace Oro\Bundle\ProductBundle\ImportExport\Configuration;

use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfiguration;
use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfigurationInterface;
use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfigurationProviderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provides import/export configuration for product entities.
 *
 * This provider defines the configuration settings for product import and export operations,
 * including processor aliases, entity class, and UI labels used throughout the import/export interface.
 */
class ProductImportExportConfigurationProvider implements ImportExportConfigurationProviderInterface
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     *
     * @throws \InvalidArgumentException
     */
    #[\Override]
    public function get(): ImportExportConfigurationInterface
    {
        return new ImportExportConfiguration([
            ImportExportConfiguration::FIELD_ENTITY_CLASS => Product::class,
            ImportExportConfiguration::FIELD_EXPORT_PROCESSOR_ALIAS => 'oro_product_product',
            ImportExportConfiguration::FIELD_EXPORT_TEMPLATE_PROCESSOR_ALIAS => 'oro_product_product_export_template',
            ImportExportConfiguration::FIELD_IMPORT_PROCESSOR_ALIAS => 'oro_product_product.add_or_replace',
            ImportExportConfiguration::FIELD_IMPORT_VALIDATION_BUTTON_LABEL =>
                $this->translator->trans('oro.product.import_validation.button.label'),
            ImportExportConfiguration::FIELD_EXPORT_BUTTON_LABEL =>
                $this->translator->trans('oro.product.export.button.label'),
            ImportExportConfiguration::FIELD_IMPORT_ENTITY_LABEL =>
                $this->translator->trans('oro.product.import.entity.label'),
        ]);
    }
}
