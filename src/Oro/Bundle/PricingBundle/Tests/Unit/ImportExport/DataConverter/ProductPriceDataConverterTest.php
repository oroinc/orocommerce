<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\ImportExport\DataConverter;

use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Converter\RelationCalculator;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\PricingBundle\ImportExport\DataConverter\ProductPriceDataConverter;
use PHPUnit\Framework\TestCase;

class ProductPriceDataConverterTest extends TestCase
{
    private const PRODUCT_SKU = 'sku';
    private const QUANTITY = '22';
    private const UNIT_CODE = 'unit';
    private const PRICE = '10.47';
    private const CURRENCY = 'USD';
    private const PRICE_LIST_ID = 4;

    private ProductPriceDataConverter $converter;

    #[\Override]
    protected function setUp(): void
    {
        $this->converter = new ProductPriceDataConverter(
            $this->createMock(FieldHelper::class),
            $this->createMock(RelationCalculator::class),
            $this->createMock(LocaleSettings::class)
        );
    }

    public function testConvertToImportFormat()
    {
        self::assertSame(
            $this->getBackendFormatData(),
            $this->converter->convertToImportFormat($this->getFileFormatData())
        );
    }

    public function testConvertToImportFormatWithContext()
    {
        $context = $this->createMock(ContextInterface::class);
        $context->expects(self::once())
            ->method('getOption')
            ->willReturn(self::PRICE_LIST_ID);

        $this->converter->setImportExportContext($context);

        $expected = $this->getBackendFormatData();
        $expected['priceList'] = ['id' => self::PRICE_LIST_ID];

        self::assertSame(
            $expected,
            $this->converter->convertToImportFormat($this->getFileFormatData())
        );
    }

    public function testConvertToExportFormat()
    {
        self::assertSame(
            $this->getFileFormatData(),
            $this->converter->convertToExportFormat($this->getBackendFormatData())
        );
    }

    private function getFileFormatData(): array
    {
        return [
            'Product SKU' => self::PRODUCT_SKU,
            'Quantity' => self::QUANTITY,
            'Unit Code' => self::UNIT_CODE,
            'Price' => self::PRICE,
            'Currency' => self::CURRENCY,
        ];
    }

    private function getBackendFormatData(): array
    {
        return [
            'product' => [
                'sku' => self::PRODUCT_SKU
            ],
            'quantity' => self::QUANTITY,
            'unit' => [
                'code' => self::UNIT_CODE
            ],
            'value' => self::PRICE,
            'currency' => self::CURRENCY,
        ];
    }
}
