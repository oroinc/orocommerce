<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\ImportExport\Frontend\DataConverter;

use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\ImportExportBundle\Converter\RelationCalculator;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\ImportExport\Frontend\DataConverter\ProductExportDataConverter;

class ProductExportDataConverterTest extends \PHPUnit\Framework\TestCase
{
    private FieldHelper|\PHPUnit\Framework\MockObject\MockObject $fieldHelper;

    private ConfigProvider|\PHPUnit\Framework\MockObject\MockObject $configProvider;

    private ProductExportDataConverter $dataConverter;

    protected function setUp(): void
    {
        $this->fieldHelper = $this->createMock(FieldHelper::class);

        $relationCalculator = $this->createMock(RelationCalculator::class);
        $localeSettings = $this->createMock(LocaleSettings::class);
        $this->configProvider = $this->createMock(ConfigProvider::class);

        $this->dataConverter = new ProductExportDataConverter($this->fieldHelper, $relationCalculator, $localeSettings);
        $this->dataConverter->setEntityName(Product::class);
        $this->dataConverter->setConfigProvider($this->configProvider);
    }

    public function testConvertToExportFormat(): void
    {
        $skuField = ['name' => 'sku', 'type' => 'string', 'label' => 'Sku'];
        $typeField = ['name' => 'type', 'type' => 'string', 'label' => 'Type'];
        $nameField = ['name' => 'names', 'type' => 'ref-many', 'label' => 'Names'];
        $this->fieldHelper->expects(self::exactly(2))
            ->method('getEntityFields')
            ->willReturn([$skuField, $typeField, $nameField]);

        $this->fieldHelper->expects(self::any())
            ->method('isRelation')
            ->willReturnMap([[$skuField, false], [$nameField, true]]);

        $this->configProvider->expects(self::any())
            ->method('getConfig')
            ->willReturnMap(
                [
                    [Product::class, 'sku', $this->getConfig(Product::class, ['use_in_export' => true])],
                    [Product::class, 'type', $this->getConfig(Product::class, [])],
                    [Product::class, 'names', $this->getConfig(Product::class, [])],
                ]
            );

        $result = $this->dataConverter->convertToExportFormat(['sku' => '1234', 'names' => 'Test product']);
        self::assertArrayHasKey('sku', $result);
        self::assertArrayHasKey('name', $result);
        self::assertArrayNotHasKey('type', $result);
        self::assertEquals('1234', $result['sku']);
        self::assertEquals('Test product', $result['name']);
    }

    public function testConvertToExportFormatWithoutEnabledAttributes(): void
    {
        $skuField = ['name' => 'sku', 'type' => 'string', 'label' => 'Sku'];
        $nameField = ['name' => 'names', 'type' => 'ref-many', 'label' => 'Names'];
        $this->fieldHelper->expects(self::exactly(2))
            ->method('getEntityFields')
            ->willReturn([$skuField, $nameField]);

        $this->fieldHelper->expects(self::any())
            ->method('isRelation')
            ->willReturnMap([[$skuField, false], [$nameField, true]]);

        $this->configProvider->expects(self::any())
            ->method('getConfig')
            ->willReturnMap(
                [
                    [Product::class, 'sku', $this->getConfig(Product::class, [])],
                    [Product::class, 'names', $this->getConfig(Product::class, [])],
                ]
            );

        $result = $this->dataConverter->convertToExportFormat(['names' => 'Test product']);
        self::assertArrayNotHasKey('sku', $result);
        self::assertArrayHasKey('name', $result);
        self::assertEquals('Test product', $result['name']);
    }

    private function getConfig(string $className, array $values): Config
    {
        return new Config(new EntityConfigId('attribute', $className), $values);
    }
}
