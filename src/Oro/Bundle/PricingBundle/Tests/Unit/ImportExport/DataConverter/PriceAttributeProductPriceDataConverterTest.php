<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\ImportExport\DataConverter;

use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\ImportExportBundle\Converter\RelationCalculator;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\PricingBundle\ImportExport\DataConverter\PriceAttributeProductPriceDataConverter;
use PHPUnit\Framework\TestCase;

class PriceAttributeProductPriceDataConverterTest extends TestCase
{
    private const PRODUCT_SKU = 'sku';
    private const PRICE_ATTRIBUTE = 'MAP';
    private const UNIT_CODE = 'unit';
    private const CURRENCY = 'USD';
    private const PRICE = '10.47';

    private PriceAttributeProductPriceDataConverter $converter;

    #[\Override]
    protected function setUp(): void
    {
        $this->converter = new PriceAttributeProductPriceDataConverter(
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
            'Price Attribute' => self::PRICE_ATTRIBUTE,
            'Unit Code' => self::UNIT_CODE,
            'Currency' => self::CURRENCY,
            'Price' => self::PRICE,
        ];
    }

    private function getBackendFormatData(): array
    {
        return [
            'product' => [
                'sku' => self::PRODUCT_SKU
            ],
            'priceList' => [
                'name' => self::PRICE_ATTRIBUTE
            ],
            'unit' => [
                'code' => self::UNIT_CODE
            ],
            'currency' => self::CURRENCY,
            'value' => self::PRICE,
        ];
    }
}
