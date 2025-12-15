<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Tests\Unit\Provider;

use Oro\Bundle\ProductBundle\Provider\ProductFallbackFieldProvider;
use Oro\Bundle\ThemeBundle\Fallback\Provider\ThemeConfigurationFallbackProvider;
use PHPUnit\Framework\TestCase;

final class ProductFallbackFieldProviderTest extends TestCase
{
    private ProductFallbackFieldProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->provider = new ProductFallbackFieldProvider();
    }

    public function testGetFieldsByFallbackId(): void
    {
        $result = $this->provider->getFieldsByFallbackId();

        self::assertIsArray($result);
        self::assertArrayHasKey(ThemeConfigurationFallbackProvider::FALLBACK_ID, $result);

        $fields = $result[ThemeConfigurationFallbackProvider::FALLBACK_ID];
        self::assertIsArray($fields);
        self::assertCount(1, $fields);
        self::assertContains('pageTemplate', $fields);
    }

    public function testGetFieldsByFallbackIdReturnsCorrectStructure(): void
    {
        $result = $this->provider->getFieldsByFallbackId();

        $expected = [
            ThemeConfigurationFallbackProvider::FALLBACK_ID => [
                'pageTemplate',
            ],
        ];

        self::assertEquals($expected, $result);
    }

    public function testGetFieldsByFallbackIdReturnsSameResultOnMultipleCalls(): void
    {
        $result1 = $this->provider->getFieldsByFallbackId();
        $result2 = $this->provider->getFieldsByFallbackId();

        self::assertEquals($result1, $result2);
    }
}
