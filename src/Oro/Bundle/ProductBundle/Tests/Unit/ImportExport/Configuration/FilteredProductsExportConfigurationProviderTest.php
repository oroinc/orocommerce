<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\ImportExport\Configuration;

use Oro\Bundle\DataGridBundle\ImportExport\FilteredEntityReader;
use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfiguration;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\ImportExport\Configuration\FilteredProductsExportConfigurationProvider;
use Symfony\Contracts\Translation\TranslatorInterface;

class FilteredProductsExportConfigurationProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var FilteredProductsExportConfigurationProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->provider = new FilteredProductsExportConfigurationProvider($this->translator);
    }

    public function testGet()
    {
        $this->translator
            ->expects($this->once())
            ->method('trans')
            ->with('oro.product.export.filtered_products.label')
            ->willReturn('Some label');

        $expected = new ImportExportConfiguration([
            ImportExportConfiguration::FIELD_ENTITY_CLASS => Product::class,
            ImportExportConfiguration::FIELD_EXPORT_JOB_NAME => 'export_filtered_to_csv',
            ImportExportConfiguration::FIELD_EXPORT_PROCESSOR_ALIAS => 'oro_product_product',
            ImportExportConfiguration::FIELD_EXPORT_BUTTON_LABEL => 'Some label',
            ImportExportConfiguration::FIELD_ROUTE_OPTIONS =>
                [FilteredEntityReader::FILTERED_RESULTS_GRID => 'products-grid']
        ]);

        $this->assertEquals($expected, $this->provider->get());
    }
}
