<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\ImportExport\DataConverter;

use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\ImportExportBundle\Converter\RelationCalculator;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\PricingBundle\ImportExport\DataConverter\PriceAttributeProductPriceDataConverter;
use PHPUnit\Framework\TestCase;

class PriceAttributeProductPriceDataConverterTest extends TestCase
{
    const PRODUCT_SKU = 'sku';
    const PRICE_ATTRIBUTE = 'MAP';
    const UNIT_CODE = 'unit';
    const CURRENCY = 'USD';
    const PRICE = '10.47';

    /**
     * @var PriceAttributeProductPriceDataConverter
     */
    private $converter;

    protected function setUp()
    {
        $this->converter = new PriceAttributeProductPriceDataConverter(
            $this->createMock(FieldHelper::class),
            $this->createMock(RelationCalculator::class),
            $this->createMock(LocaleSettings::class)
        );
    }

    public function testConvertToImportFormat()
    {
        static::assertSame(
            $this->getBackendFormatData(),
            $this->converter->convertToImportFormat($this->getFileFormatData())
        );
    }

    public function testConvertToExportFormat()
    {
        static::assertSame(
            $this->getFileFormatData(),
            $this->converter->convertToExportFormat($this->getBackendFormatData())
        );
    }

    /**
     * @return array
     */
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

    /**
     * @return array
     */
    private function getBackendFormatData():array
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
