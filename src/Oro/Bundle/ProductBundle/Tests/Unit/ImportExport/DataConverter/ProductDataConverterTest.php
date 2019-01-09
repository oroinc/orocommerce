<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\ImportExport\DataConverter;

use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\ImportExportBundle\Converter\RelationCalculator;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\ProductBundle\ImportExport\DataConverter\ProductDataConverter;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ProductDataConverterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ProductDataConverter
     */
    private $dataConverter;

    /**
     * @var FieldHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $fieldHelper;

    /**
     * @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $eventDispatcher;

    /**
     * @var LocaleSettings|\PHPUnit\Framework\MockObject\MockObject
     */
    private $localeSettings;

    protected function setUp()
    {
        $this->fieldHelper = $this->createMock(FieldHelper::class);

        /** @var \PHPUnit\Framework\MockObject\MockObject|RelationCalculator $relationCalculator */
        $relationCalculator = $this->createMock(RelationCalculator::class);

        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->localeSettings = $this->createMock(LocaleSettings::class);

        $this->dataConverter = new ProductDataConverter($this->fieldHelper, $relationCalculator, $this->localeSettings);
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
                        $this->equalTo('oro_product.data_converter.backend_header')
                    ),
                    $this->isInstanceOf('Oro\Bundle\ProductBundle\ImportExport\Event\ProductDataConverterEvent'),
                ],
                [
                    $this->logicalAnd(
                        $this->isType('string'),
                        $this->equalTo('oro_product.data_converter.convert_to_export')
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
                        $this->equalTo('oro_product.data_converter.convert_to_import')
                    ),
                    $this->isInstanceOf('Oro\Bundle\ProductBundle\ImportExport\Event\ProductDataConverterEvent'),
                ]
            );

        $result = $this->dataConverter->convertToImportFormat(['sku' => 'test']);
        $this->assertArrayHasKey('sku', $result);
        $this->assertEquals($result['sku'], 'test');
    }
}
