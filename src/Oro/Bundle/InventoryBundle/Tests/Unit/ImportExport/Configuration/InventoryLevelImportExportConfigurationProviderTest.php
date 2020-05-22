<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\ImportExport\Configuration;

use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfiguration;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\InventoryBundle\ImportExport\Configuration\InventoryLevelImportExportConfigurationProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class InventoryLevelImportExportConfigurationProviderTest extends TestCase
{
    /**
     * @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $translator;

    /**
     * @var InventoryLevelImportExportConfigurationProvider
     */
    private $provider;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->provider = new InventoryLevelImportExportConfigurationProvider($this->translator);
    }

    public function testGet()
    {
        $this->translator
            ->expects(static::once())
            ->method('trans')
            ->with('oro.inventory.export.popup.title')
            ->willReturn('title');

        static::assertEquals(
            new ImportExportConfiguration([
                ImportExportConfiguration::FIELD_ENTITY_CLASS => InventoryLevel::class,
                ImportExportConfiguration::FIELD_EXPORT_JOB_NAME => 'inventory_level_export_to_csv',
                ImportExportConfiguration::FIELD_EXPORT_POPUP_TITLE => 'title',
                ImportExportConfiguration::FIELD_EXPORT_PROCESSOR_ALIAS => [
                    'oro_product.inventory_status_only' => 'oro.product.export.inventory_status_only',
                    'oro_inventory.detailed_inventory_levels' => 'oro.inventory.export.detailed_inventory_levels',
                ],
                ImportExportConfiguration::FIELD_IMPORT_PROCESSOR_ALIAS => 'oro_inventory.inventory_level',
                ImportExportConfiguration::FIELD_EXPORT_TEMPLATE_PROCESSOR_ALIAS => [
                    'oro_product.inventory_status_only_template' => 'oro.product.export.inventory_status_only',
                    'oro_inventory.detailed_inventory_levels_template' =>
                        'oro.inventory.export.detailed_inventory_levels',
                ],
            ]),
            $this->provider->get()
        );
    }
}
