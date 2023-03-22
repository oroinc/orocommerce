<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\ImportExport\DataConverter;

use Oro\Bundle\ImportExportBundle\Exception\LogicException;
use Oro\Bundle\ProductBundle\ImportExport\DataConverter\RelatedProductDataConverter;

class RelatedProductDataConverterTest extends \PHPUnit\Framework\TestCase
{
    private RelatedProductDataConverter $dataConverter;

    protected function setUp(): void
    {
        $this->dataConverter = new RelatedProductDataConverter();
    }

    /**
     * @dataProvider convertDataProvider
     */
    public function testConvertImportExport(array $importedRecord, array $exportedRecord): void
    {
        $this->assertEquals($exportedRecord, $this->dataConverter->convertToExportFormat($importedRecord));
        $this->assertEquals($importedRecord, $this->dataConverter->convertToImportFormat($exportedRecord));
    }

    public function convertDataProvider(): array
    {
        return [
            'no data' => [
                'importedRecord' => [],
                'exportedRecord' => [
                    'SKU' => '',
                    'Related SKUs' => '',
                ],
            ],
            'plain data' => [
                'importedRecord' => [
                    'sku' => 'sku1',
                    'relatedItem'  => 'sku2,sku3,sku4',
                ],
                'exportedRecord' => [
                    'SKU' => 'sku1',
                    'Related SKUs' => 'sku2,sku3,sku4',
                ],
            ],
        ];
    }

    public function testConvertToExportFormatIncorrectKey(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Delimiter ":" is not allowed in keys');

        $this->dataConverter->convertToExportFormat(['owner:firstName' => 'John']);
    }

    public function testConvertToImportIncorrectKey(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Can\'t set nested value under key "owner"');

        $this->dataConverter->convertToImportFormat(['owner' => 'John Doe', 'owner:firstName' => 'John']);
    }
}
