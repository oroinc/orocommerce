<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\ImportExport\Configuration;

use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfiguration;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\ImportExport\Configuration\ProductImportExportConfigurationProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProductImportExportConfigurationProviderTest extends TestCase
{
    /**
     * @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $translator;

    /**
     * @var ProductImportExportConfigurationProvider
     */
    private $provider;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->provider = new ProductImportExportConfigurationProvider($this->translator);
    }

    public function testGet()
    {
        $this->translator
            ->expects(static::exactly(3))
            ->method('trans')
            ->withConsecutive(
                ['oro.product.import_validation.button.label'],
                ['oro.product.export.button.label'],
                ['oro.product.import.entity.label']
            )
            ->willReturnOnConsecutiveCalls(
                '1',
                '2',
                'someLabel'
            );

        $expected = new ImportExportConfiguration([
            ImportExportConfiguration::FIELD_ENTITY_CLASS => Product::class,
            ImportExportConfiguration::FIELD_EXPORT_PROCESSOR_ALIAS => 'oro_product_product',
            ImportExportConfiguration::FIELD_EXPORT_TEMPLATE_PROCESSOR_ALIAS => 'oro_product_product_export_template',
            ImportExportConfiguration::FIELD_IMPORT_PROCESSOR_ALIAS => 'oro_product_product.add_or_replace',
            ImportExportConfiguration::FIELD_IMPORT_VALIDATION_BUTTON_LABEL => '1',
            ImportExportConfiguration::FIELD_EXPORT_BUTTON_LABEL => '2',
            ImportExportConfiguration::FIELD_IMPORT_ENTITY_LABEL => 'someLabel',
        ]);

        static::assertEquals($expected, $this->provider->get());
    }
}
