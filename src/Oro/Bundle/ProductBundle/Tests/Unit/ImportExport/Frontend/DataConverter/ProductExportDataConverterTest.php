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
    private ProductExportDataConverter $dataConverter;

    /** @var FieldHelper|\PHPUnit\Framework\MockObject\MockObject */
    private FieldHelper $fieldHelper;

    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private ConfigProvider $attributeConfigProvider;

    protected function setUp(): void
    {
        $this->fieldHelper = $this->createMock(FieldHelper::class);

        /** @var \PHPUnit\Framework\MockObject\MockObject|RelationCalculator $relationCalculator */
        $relationCalculator = $this->createMock(RelationCalculator::class);
        /** @var LocaleSettings|\PHPUnit\Framework\MockObject\MockObject */
        $localeSettings = $this->createMock(LocaleSettings::class);
        $this->attributeConfigProvider = $this->createMock(ConfigProvider::class);

        $this->dataConverter = new ProductExportDataConverter($this->fieldHelper, $relationCalculator, $localeSettings);
        $this->dataConverter->setEntityName(Product::class);
        $this->dataConverter->setAttributeConfigProvider($this->attributeConfigProvider);
    }

    public function testConvertToExportFormat(): void
    {
        $this->fieldHelper->expects($this->exactly(2))
            ->method('getFields')
            ->willReturn([
                ['name' => 'sku', 'type' => 'string', 'label' => 'Sku'],
                ['name' => 'type', 'type' => 'string', 'label' => 'Type']
            ]);

        $this->attributeConfigProvider->expects($this->exactly(2))
            ->method('getConfig')
            ->willReturnMap(
                [
                    [Product::class, 'sku', $this->getConfig(Product::class, ['use_in_export' => true])],
                    [Product::class, 'type', $this->getConfig(Product::class, [])],
                ]
            );

        $result = $this->dataConverter->convertToExportFormat(['sku' => '1234', 'name' => 'Test product']);
        $this->assertArrayHasKey('sku', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayNotHasKey('type', $result);
        $this->assertEquals('1234', $result['sku']);
        $this->assertEquals('Test product', $result['name']);
    }

    public function testConvertToExportFormatWithoutEnabledAttributes(): void
    {
        $this->fieldHelper->expects($this->exactly(2))
            ->method('getFields')
            ->willReturn([
                ['name' => 'sku', 'type' => 'string', 'label' => 'Sku'],
            ]);

        $this->attributeConfigProvider->expects($this->exactly(1))
            ->method('getConfig')
            ->willReturn($this->getConfig(Product::class, []));

        $result = $this->dataConverter->convertToExportFormat(['name' => 'Test product']);
        $this->assertArrayNotHasKey('sku', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertEquals('Test product', $result['name']);
    }

    private function getConfig(string $className, array $values): Config
    {
        return new Config(
            new EntityConfigId('attribute', $className),
            $values
        );
    }
}
