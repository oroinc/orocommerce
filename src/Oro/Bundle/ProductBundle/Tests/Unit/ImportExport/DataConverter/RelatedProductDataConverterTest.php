<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\ImportExport\DataConverter;

use Oro\Bundle\ProductBundle\ImportExport\DataConverter\RelatedProductDataConverter;

class RelatedProductDataConverterTest extends \PHPUnit\Framework\TestCase
{
    /** @var RelatedProductDataConverter */
    private $dataConverter;

    protected function setUp(): void
    {
        $this->dataConverter = new RelatedProductDataConverter();
    }

    /**
     * @dataProvider convertDataProvider
     *
     * @param array $importedRecord
     * @param array $exportedRecord
     */
    public function testConvertImportExport(array $importedRecord, array $exportedRecord): void
    {
        $this->assertEquals($exportedRecord, $this->dataConverter->convertToExportFormat($importedRecord));
        $this->assertEquals($importedRecord, $this->dataConverter->convertToImportFormat($exportedRecord));
    }

    /**
     * @return array
     */
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
                    'related_skus'  => 'sku2,sku3,sku4',
                ],
                'exportedRecord' => [
                    'SKU' => 'sku1',
                    'Related SKUs' => 'sku2,sku3,sku4',
                ],
            ],
        ];
    }

    /**
     * @expectedException \Oro\Bundle\ImportExportBundle\Exception\LogicException
     * @expectedExceptionMessage Delimiter ":" is not allowed in keys
     */
    public function testConvertToExportFormatIncorrectKey(): void
    {
        $this->dataConverter->convertToExportFormat(['owner:firstName' => 'John']);
    }

    /**
     * @expectedException \Oro\Bundle\ImportExportBundle\Exception\LogicException
     * @expectedExceptionMessage Can't set nested value under key "owner"
     */
    public function testConvertToImportIncorrectKey(): void
    {
        $this->dataConverter->convertToImportFormat(['owner' => 'John Doe', 'owner:firstName' => 'John']);
    }
}
