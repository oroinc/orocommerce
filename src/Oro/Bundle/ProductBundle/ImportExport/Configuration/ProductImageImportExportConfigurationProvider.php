<?php

namespace Oro\Bundle\ProductBundle\ImportExport\Configuration;

use Oro\Bundle\GaufretteBundle\FileManager;
use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfiguration;
use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfigurationInterface;
use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfigurationProviderInterface;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Import/export configuration provider for Product images.
 */
class ProductImageImportExportConfigurationProvider implements ImportExportConfigurationProviderInterface
{
    /** @var TranslatorInterface */
    private $translator;

    /** @var FileManager */
    private $fileManager;

    public function __construct(TranslatorInterface $translator, FileManager $fileManager)
    {
        $this->translator = $translator;
        $this->fileManager = $fileManager;
    }

    /**
     * {@inheritDoc}
     *
     * @throws \InvalidArgumentException
     */
    public function get(): ImportExportConfigurationInterface
    {
        return new ImportExportConfiguration([
            ImportExportConfiguration::FIELD_ENTITY_CLASS => ProductImage::class,
            ImportExportConfiguration::FIELD_EXPORT_TEMPLATE_PROCESSOR_ALIAS => 'oro_product_image_export_template',
            ImportExportConfiguration::FIELD_IMPORT_PROCESSOR_ALIAS => 'oro_product_image.add_or_replace',
            ImportExportConfiguration::FIELD_IMPORT_ENTITY_LABEL =>
                $this->translator->trans('oro.product.productimage.entity_plural_label'),
            ImportExportConfiguration::FIELD_IMPORT_ADDITIONAL_NOTICES => [
                $this->translator->trans(
                    'oro.product.productimage.import.notice',
                    ['%adapter_description%' => $this->fileManager->getAdapterDescription()]
                )
            ]
        ]);
    }
}
