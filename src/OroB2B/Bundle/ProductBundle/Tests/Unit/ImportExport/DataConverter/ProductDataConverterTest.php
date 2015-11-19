<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\ImportExport\DataConverter;

use Oro\Bundle\ImportExportBundle\Field\FieldHelper;

use OroB2B\Bundle\ProductBundle\ImportExport\DataConverter\ProductDataConverter;

class ProductDataConverterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ProductDataConverter
     */
    protected $dataConverter;

    /**
     * @var FieldHelper
     */
    protected $fieldHelper;

    protected function setUp()
    {
        $this->fieldHelper = $this->getMockBuilder('Oro\Bundle\ImportExportBundle\Field\FieldHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $relationCalculator = $this->getMockBuilder('Oro\Bundle\ImportExportBundle\Converter\RelationCalculator')
            ->disableOriginalConstructor()
            ->getMock();

        $this->dataConverter = new ProductDataConverter($this->fieldHelper, $relationCalculator);
        $this->dataConverter->setEntityName('OroB2B\Bundle\ProductBundle\Entity\Product');
    }

    public function testConvertToExportFormat()
    {
        $this->fieldHelper->expects($this->once())
            ->method('getFields')
            ->willReturn([['name' => 'sku', 'type' => 'string', 'label' => 'sku']]);

        $result = $this->dataConverter->convertToExportFormat(['sku' => 'test']);
        $this->assertArrayHasKey('sku', $result);
        $this->assertEquals($result['sku'], 'test');
    }

    public function testConvertToImportFormat()
    {
        $this->fieldHelper->expects($this->once())
            ->method('getFields')
            ->willReturn([['name' => 'sku', 'type' => 'string', 'label' => 'sku']]);

        $result = $this->dataConverter->convertToImportFormat(['sku' => 'test']);
        $this->assertArrayHasKey('sku', $result);
        $this->assertEquals($result['sku'], 'test');
    }
}