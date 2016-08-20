<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\ImportExport\DataConverter;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\ImportExportBundle\Converter\RelationCalculator;
use Oro\Bundle\ImportExportBundle\Field\FieldHelper;
use Oro\Bundle\ProductBundle\ImportExport\DataConverter\ProductDataConverter;

class ProductDataConverterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ProductDataConverter
     */
    protected $dataConverter;

    /**
     * @var FieldHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $fieldHelper;

    /**
     * @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $eventDispatcher;

    protected function setUp()
    {
        $this->fieldHelper = $this->getMockBuilder('Oro\Bundle\ImportExportBundle\Field\FieldHelper')
            ->disableOriginalConstructor()
            ->getMock();

        /** @var \PHPUnit_Framework_MockObject_MockObject|RelationCalculator $relationCalculator */
        $relationCalculator = $this->getMockBuilder('Oro\Bundle\ImportExportBundle\Converter\RelationCalculator')
            ->disableOriginalConstructor()
            ->getMock();

        $this->eventDispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');

        $this->dataConverter = new ProductDataConverter($this->fieldHelper, $relationCalculator);
        $this->dataConverter->setEntityName('Oro\Bundle\ProductBundle\Entity\Product');
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

    public function testConvertToExportFormatWithEventDispatcher()
    {
        $this->fieldHelper->expects($this->once())
            ->method('getFields')
            ->willReturn([['name' => 'sku', 'type' => 'string', 'label' => 'sku']]);

        $this->dataConverter->setEventDispatcher($this->eventDispatcher);
        $this->eventDispatcher->expects($this->exactly(2))->method('dispatch')
            ->withConsecutive(
                [
                    $this->logicalAnd(
                        $this->isType('string'),
                        $this->equalTo('orob2b_product.data_converter.backend_header')
                    ),
                    $this->isInstanceOf('Oro\Bundle\ProductBundle\ImportExport\Event\ProductDataConverterEvent'),
                ],
                [
                    $this->logicalAnd(
                        $this->isType('string'),
                        $this->equalTo('orob2b_product.data_converter.convert_to_export')
                    ),
                    $this->isInstanceOf('Oro\Bundle\ProductBundle\ImportExport\Event\ProductDataConverterEvent'),
                ]
            );

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

    public function testConvertToImportFormatWithEventDispatcher()
    {
        $this->fieldHelper->expects($this->once())
            ->method('getFields')
            ->willReturn([['name' => 'sku', 'type' => 'string', 'label' => 'sku']]);

        $this->dataConverter->setEventDispatcher($this->eventDispatcher);
        $this->eventDispatcher->expects($this->once())->method('dispatch')
            ->withConsecutive(
                [
                    $this->logicalAnd(
                        $this->isType('string'),
                        $this->equalTo('orob2b_product.data_converter.convert_to_import')
                    ),
                    $this->isInstanceOf('Oro\Bundle\ProductBundle\ImportExport\Event\ProductDataConverterEvent'),
                ]
            );

        $result = $this->dataConverter->convertToImportFormat(['sku' => 'test']);
        $this->assertArrayHasKey('sku', $result);
        $this->assertEquals($result['sku'], 'test');
    }
}
