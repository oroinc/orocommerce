<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\ImportExport\Configuration;

use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfiguration;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\ImportExport\Configuration\ProductImportExportConfigurationProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\TranslatorInterface;

class ProductImportExportConfigurationProviderTest extends TestCase
{
    /**
     * @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $translator;

    /**
     * @var ProductImportExportConfigurationProvider
     */
    private $provider;

    protected function setUp()
    {
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->provider = new ProductImportExportConfigurationProvider($this->translator);
    }

    public function testGet()
    {
        $this->translator
            ->expects(static::exactly(5))
            ->method('trans')
            ->withConsecutive(
                ['oro.product.import.button.label'],
                ['oro.product.import_validation.button.label'],
                ['oro.product.export_template.button.label'],
                ['oro.product.export.button.label'],
                ['oro.product.import.popup.title']
            )
            ->willReturnOnConsecutiveCalls(
                '1',
                '2',
                '3',
                '4',
                '5'
            );

        $expected = new ImportExportConfiguration([
            ImportExportConfiguration::FIELD_ENTITY_CLASS => Product::class,
            ImportExportConfiguration::FIELD_EXPORT_PROCESSOR_ALIAS => 'oro_product_product',
            ImportExportConfiguration::FIELD_EXPORT_TEMPLATE_PROCESSOR_ALIAS => 'oro_product_product_export_template',
            ImportExportConfiguration::FIELD_IMPORT_PROCESSOR_ALIAS => 'oro_product_product.add_or_replace',
            ImportExportConfiguration::FIELD_DATA_GRID_NAME => 'products-grid',
            ImportExportConfiguration::FIELD_IMPORT_BUTTON_LABEL => '1',
            ImportExportConfiguration::FIELD_IMPORT_VALIDATION_BUTTON_LABEL => '2',
            ImportExportConfiguration::FIELD_EXPORT_TEMPLATE_BUTTON_LABEL => '3',
            ImportExportConfiguration::FIELD_EXPORT_BUTTON_LABEL => '4',
            ImportExportConfiguration::FIELD_IMPORT_POPUP_TITLE => '5',
        ]);

        static::assertEquals($expected, $this->provider->get());
    }
}
