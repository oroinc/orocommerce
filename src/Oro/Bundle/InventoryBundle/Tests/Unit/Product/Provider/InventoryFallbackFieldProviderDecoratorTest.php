<?php

declare(strict_types=1);

namespace Oro\Bundle\InventoryBundle\Tests\Unit\Product\Provider;

use Oro\Bundle\CatalogBundle\Fallback\Provider\CategoryFallbackProvider;
use Oro\Bundle\InventoryBundle\Product\Provider\InventoryFallbackFieldProviderDecorator;
use Oro\Bundle\InventoryBundle\Provider\UpcomingProductProvider;
use Oro\Bundle\ProductBundle\Provider\ProductFallbackFieldProviderInterface;
use Oro\Bundle\ThemeBundle\Fallback\Provider\ThemeConfigurationFallbackProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class InventoryFallbackFieldProviderDecoratorTest extends TestCase
{
    private ProductFallbackFieldProviderInterface&MockObject $innerProvider;
    private InventoryFallbackFieldProviderDecorator $decorator;

    #[\Override]
    protected function setUp(): void
    {
        $this->innerProvider = $this->createMock(ProductFallbackFieldProviderInterface::class);
        $this->decorator = new InventoryFallbackFieldProviderDecorator($this->innerProvider);
    }

    public function testGetFieldsByFallbackIdMergesInnerProviderFields(): void
    {
        $innerFields = [
            ThemeConfigurationFallbackProvider::FALLBACK_ID => [
                'pageTemplate',
            ],
        ];

        $this->innerProvider->expects(self::once())
            ->method('getFieldsByFallbackId')
            ->willReturn($innerFields);

        $result = $this->decorator->getFieldsByFallbackId();

        self::assertArrayHasKey(ThemeConfigurationFallbackProvider::FALLBACK_ID, $result);
        self::assertArrayHasKey(CategoryFallbackProvider::FALLBACK_ID, $result);
        self::assertEquals(['pageTemplate'], $result[ThemeConfigurationFallbackProvider::FALLBACK_ID]);
    }

    public function testGetFieldsByFallbackIdContainsInventoryFields(): void
    {
        $this->innerProvider->expects(self::once())
            ->method('getFieldsByFallbackId')
            ->willReturn([]);

        $result = $this->decorator->getFieldsByFallbackId();

        self::assertArrayHasKey(CategoryFallbackProvider::FALLBACK_ID, $result);

        $inventoryFields = $result[CategoryFallbackProvider::FALLBACK_ID];
        self::assertIsArray($inventoryFields);

        $expectedFields = [
            'manageInventory',
            'highlightLowInventory',
            'inventoryThreshold',
            'lowInventoryThreshold',
            'backOrder',
            'decrementQuantity',
            'minimumQuantityToOrder',
            'maximumQuantityToOrder',
            UpcomingProductProvider::IS_UPCOMING,
        ];

        self::assertCount(count($expectedFields), $inventoryFields);

        foreach ($expectedFields as $field) {
            self::assertContains($field, $inventoryFields, sprintf('Field "%s" should be present', $field));
        }
    }

    public function testGetFieldsByFallbackIdWithEmptyInnerProvider(): void
    {
        $this->innerProvider->expects(self::once())
            ->method('getFieldsByFallbackId')
            ->willReturn([]);

        $result = $this->decorator->getFieldsByFallbackId();

        self::assertIsArray($result);
        self::assertCount(1, $result);
        self::assertArrayHasKey(CategoryFallbackProvider::FALLBACK_ID, $result);

        $inventoryFields = $result[CategoryFallbackProvider::FALLBACK_ID];
        self::assertNotEmpty($inventoryFields);
        self::assertContains('manageInventory', $inventoryFields);
    }

    public function testGetFieldsByFallbackIdWithMultipleInnerProviderFallbackIds(): void
    {
        $innerFields = [
            ThemeConfigurationFallbackProvider::FALLBACK_ID => [
                'pageTemplate',
            ],
            'custom_fallback_id' => [
                'customField1',
                'customField2',
            ],
        ];

        $this->innerProvider->expects(self::once())
            ->method('getFieldsByFallbackId')
            ->willReturn($innerFields);

        $result = $this->decorator->getFieldsByFallbackId();

        self::assertArrayHasKey(ThemeConfigurationFallbackProvider::FALLBACK_ID, $result);
        self::assertArrayHasKey('custom_fallback_id', $result);

        self::assertArrayHasKey(CategoryFallbackProvider::FALLBACK_ID, $result);

        self::assertCount(3, $result);
    }
}
