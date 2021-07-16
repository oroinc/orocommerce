<?php

namespace Oro\Bundle\InventoryBundle\ImportExport\Configuration;

use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfiguration;
use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfigurationInterface;
use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfigurationProviderInterface;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\InventoryBundle\Form\Extension\InventoryLevelExportTemplateTypeExtension;
use Oro\Bundle\InventoryBundle\Form\Extension\InventoryLevelExportTypeExtension;
use Symfony\Contracts\Translation\TranslatorInterface;

class InventoryLevelImportExportConfigurationProvider implements ImportExportConfigurationProviderInterface
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
     * {@inheritDoc}
     *
     * @throws \InvalidArgumentException
     */
    public function get(): ImportExportConfigurationInterface
    {
        return new ImportExportConfiguration([
            ImportExportConfiguration::FIELD_ENTITY_CLASS => InventoryLevel::class,
            ImportExportConfiguration::FIELD_EXPORT_JOB_NAME => 'inventory_level_export_to_csv',
            ImportExportConfiguration::FIELD_EXPORT_POPUP_TITLE => $this->translator->trans(
                'oro.inventory.export.popup.title'
            ),
            ImportExportConfiguration::FIELD_EXPORT_PROCESSOR_ALIAS =>
                InventoryLevelExportTypeExtension::getProcessorAliases(),
            ImportExportConfiguration::FIELD_IMPORT_PROCESSOR_ALIAS => 'oro_inventory.inventory_level',
            ImportExportConfiguration::FIELD_EXPORT_TEMPLATE_PROCESSOR_ALIAS =>
                InventoryLevelExportTemplateTypeExtension::getProcessorAliases(),
        ]);
    }
}
