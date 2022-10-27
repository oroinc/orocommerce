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
    private ProductDataConverter $dataConverter;

    private FieldHelper|\PHPUnit\Framework\MockObject\MockObject $fieldHelper;

    private EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject $eventDispatcher;

    private ContextInterface|\PHPUnit\Framework\MockObject\MockObject $context;

    protected function setUp(): void
    {
        $this->fieldHelper = $this->createMock(FieldHelper::class);

        /** @var \PHPUnit\Framework\MockObject\MockObject|RelationCalculator $relationCalculator */
        $relationCalculator = $this->createMock(RelationCalculator::class);

        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $localeSettings = $this->createMock(LocaleSettings::class);
        $this->context = $this->createMock(ContextInterface::class);

        $this->dataConverter = new ProductDataConverter($this->fieldHelper, $relationCalculator, $localeSettings);
        $this->dataConverter->setEntityName(Product::class);
        $this->dataConverter->setImportExportContext($this->context);
    }

    public function testConvertToExportFormat(): void
    {
        $this->fieldHelper->expects(self::exactly(2))
            ->method('getEntityFields')
            ->willReturn([['name' => 'sku', 'type' => 'string', 'label' => 'sku']]);

        $result = $this->dataConverter->convertToExportFormat(['sku' => 'test']);
        self::assertArrayHasKey('sku', $result);
        self::assertEquals('test', $result['sku']);
    }

    public function testConvertToExportFormatWithEventDispatcher(): void
    {
        $this->fieldHelper->expects(self::exactly(2))
            ->method('getEntityFields')
            ->willReturn([['name' => 'sku', 'type' => 'string', 'label' => 'sku']]);

        $this->dataConverter->setEventDispatcher($this->eventDispatcher);

        $eventBackendHeader = new ProductDataConverterEvent(['sku']);
        $eventBackendHeader->setContext($this->context);

        $eventExport = new ProductDataConverterEvent(['sku' => 'test']);
        $eventExport->setContext($this->context);

        $this->eventDispatcher->expects(self::exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [$eventBackendHeader, 'oro_product.data_converter.backend_header'],
                [$eventExport, 'oro_product.data_converter.convert_to_export']
            );

        $result = $this->dataConverter->convertToExportFormat(['sku' => 'test']);
        self::assertArrayHasKey('sku', $result);
        self::assertEquals('test', $result['sku']);
    }

    public function testConvertToImportFormat(): void
    {
        $this->fieldHelper->expects(self::once())
            ->method('getEntityFields')
            ->willReturn([['name' => 'sku', 'type' => 'string', 'label' => 'sku']]);

        $result = $this->dataConverter->convertToImportFormat(['sku' => 'test']);
        self::assertArrayHasKey('sku', $result);
        self::assertEquals('test', $result['sku']);
    }

    public function testConvertToImportFormatWithEventDispatcher(): void
    {
        $this->fieldHelper->expects(self::once())
            ->method('getEntityFields')
            ->willReturn([['name' => 'sku', 'type' => 'string', 'label' => 'sku']]);

        $this->dataConverter->setEventDispatcher($this->eventDispatcher);

        $event = new ProductDataConverterEvent(['sku' => 'test']);
        $event->setContext($this->context);

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with($event, 'oro_product.data_converter.convert_to_import');

        $result = $this->dataConverter->convertToImportFormat(['sku' => 'test']);
        self::assertArrayHasKey('sku', $result);
        self::assertEquals('test', $result['sku']);
    }
}
