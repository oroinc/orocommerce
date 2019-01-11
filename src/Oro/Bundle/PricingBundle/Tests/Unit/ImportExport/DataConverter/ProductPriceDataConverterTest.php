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
    const PRODUCT_SKU = 'sku';
    const QUANTITY = '22';
    const UNIT_CODE = 'unit';
    const PRICE = '10.47';
    const CURRENCY = 'USD';
    const PRICE_LIST_ID = 4;

    /**
     * @var ProductPriceDataConverter
     */
    private $converter;

    protected function setUp()
    {
        $this->converter = new ProductPriceDataConverter(
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

    public function testConvertToImportFormatWithContext()
    {
        $context = $this->createMock(ContextInterface::class);
        $context->expects(static::once())
            ->method('getOption')
            ->willReturn(self::PRICE_LIST_ID);

        $this->converter->setImportExportContext($context);

        $expected = $this->getBackendFormatData();
        $expected['priceList'] = ['id' => self::PRICE_LIST_ID];

        static::assertSame(
            $expected,
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
            'Quantity' => self::QUANTITY,
            'Unit Code' => self::UNIT_CODE,
            'Price' => self::PRICE,
            'Currency' => self::CURRENCY,
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
            'quantity' => self::QUANTITY,
            'unit' => [
                'code' => self::UNIT_CODE
            ],
            'value' => self::PRICE,
            'currency' => self::CURRENCY,
        ];
    }
}
