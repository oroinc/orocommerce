<?php

namespace Oro\Bundle\ProductBundle\ImportExport\Configuration;

use Oro\Bundle\DataGridBundle\ImportExport\FilteredEntityReader;
use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfiguration;
use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfigurationInterface;
use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfigurationProviderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Filtered Products Export configuration
 */
class FilteredProductsExportConfigurationProvider implements ImportExportConfigurationProviderInterface
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
     * {@inheritdoc}
     */
    public function get(): ImportExportConfigurationInterface
    {
        return new ImportExportConfiguration([
            ImportExportConfiguration::FIELD_ENTITY_CLASS => Product::class,
            ImportExportConfiguration::FIELD_EXPORT_JOB_NAME => 'export_filtered_to_csv',
            ImportExportConfiguration::FIELD_EXPORT_PROCESSOR_ALIAS => 'oro_product_product',
            ImportExportConfiguration::FIELD_EXPORT_BUTTON_LABEL => $this->translator->trans(
                'oro.product.export.filtered_products.label'
            ),
            ImportExportConfiguration::FIELD_ROUTE_OPTIONS => [
                FilteredEntityReader::FILTERED_RESULTS_GRID => 'products-grid'
            ]
        ]);
    }
}
