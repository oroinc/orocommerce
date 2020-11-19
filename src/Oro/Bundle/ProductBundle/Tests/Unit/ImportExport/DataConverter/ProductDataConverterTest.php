<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\ImportExport\DataConverter;

use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Converter\RelationCalculator;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\ImportExport\DataConverter\ProductDataConverter;
use Oro\Bundle\ProductBundle\ImportExport\Event\ProductDataConverterEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ProductDataConverterTest extends \PHPUnit\Framework\TestCase
{
    /** @var ProductDataConverter */
    private $dataConverter;

    /** @var FieldHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $fieldHelper;

    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $eventDispatcher;

    /** @var LocaleSettings|\PHPUnit\Framework\MockObject\MockObject */
    private $localeSettings;

    /** @var ContextInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $context;

    protected function setUp()
    {
        $this->fieldHelper = $this->createMock(FieldHelper::class);

        /** @var \PHPUnit\Framework\MockObject\MockObject|RelationCalculator $relationCalculator */
        $relationCalculator = $this->createMock(RelationCalculator::class);

        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->localeSettings = $this->createMock(LocaleSettings::class);
        $this->context = $this->createMock(ContextInterface::class);

        $this->dataConverter = new ProductDataConverter($this->fieldHelper, $relationCalculator, $this->localeSettings);
        $this->dataConverter->setEntityName(Product::class);
        $this->dataConverter->setImportExportContext($this->context);
    }

    public function testConvertToExportFormat()
    {
        $this->fieldHelper->expects($this->exactly(2))
            ->method('getFields')
            ->willReturn([['name' => 'sku', 'type' => 'string', 'label' => 'sku']]);

        $result = $this->dataConverter->convertToExportFormat(['sku' => 'test']);
        $this->assertArrayHasKey('sku', $result);
        $this->assertEquals('test', $result['sku']);
    }

    public function testConvertToExportFormatWithEventDispatcher()
    {
        $this->fieldHelper->expects($this->exactly(2))
            ->method('getFields')
            ->willReturn([['name' => 'sku', 'type' => 'string', 'label' => 'sku']]);

        $this->dataConverter->setEventDispatcher($this->eventDispatcher);

        $eventBackendHeader = new ProductDataConverterEvent(['sku']);
        $eventBackendHeader->setContext($this->context);

        $eventExport = new ProductDataConverterEvent(['sku' => 'test']);
        $eventExport->setContext($this->context);

        $this->eventDispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                ['oro_product.data_converter.backend_header', $eventBackendHeader],
                ['oro_product.data_converter.convert_to_export', $eventExport]
            );

        $result = $this->dataConverter->convertToExportFormat(['sku' => 'test']);
        $this->assertArrayHasKey('sku', $result);
        $this->assertEquals('test', $result['sku']);
    }

    public function testConvertToImportFormat()
    {
        $this->fieldHelper->expects($this->once())
            ->method('getFields')
            ->willReturn([['name' => 'sku', 'type' => 'string', 'label' => 'sku']]);

        $result = $this->dataConverter->convertToImportFormat(['sku' => 'test']);
        $this->assertArrayHasKey('sku', $result);
        $this->assertEquals('test', $result['sku']);
    }

    public function testConvertToImportFormatWithEventDispatcher()
    {
        $this->fieldHelper->expects($this->once())
            ->method('getFields')
            ->willReturn([['name' => 'sku', 'type' => 'string', 'label' => 'sku']]);

        $this->dataConverter->setEventDispatcher($this->eventDispatcher);

        $event = new ProductDataConverterEvent(['sku' => 'test']);
        $event->setContext($this->context);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with('oro_product.data_converter.convert_to_import', $event);

        $result = $this->dataConverter->convertToImportFormat(['sku' => 'test']);
        $this->assertArrayHasKey('sku', $result);
        $this->assertEquals('test', $result['sku']);
    }
}
