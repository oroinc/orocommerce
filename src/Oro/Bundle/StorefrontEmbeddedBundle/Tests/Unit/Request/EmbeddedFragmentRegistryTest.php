<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Tests\Unit\Request;

use Oro\Bundle\StorefrontEmbeddedBundle\Request\EmbeddedFragmentRegistry;
use PHPUnit\Framework\TestCase;

final class EmbeddedFragmentRegistryTest extends TestCase
{
    private EmbeddedFragmentRegistry $registry;

    #[\Override]
    protected function setUp(): void
    {
        $this->registry = new EmbeddedFragmentRegistry([
            'embedded_orders' => [
                'route_name' => 'oro_order_frontend_index',
                'layout_block_ids' => ['order_grid', 'order_filters'],
            ],
        ]);
    }

    public function testHasFragmentReturnsTrueForKnownFragment(): void
    {
        self::assertTrue($this->registry->hasFragment('embedded_orders'));
    }

    public function testHasFragmentReturnsFalseForUnknownFragment(): void
    {
        self::assertFalse($this->registry->hasFragment('embedded_unknown'));
    }

    public function testGetRouteNameReturnsFragmentRouteName(): void
    {
        self::assertSame('oro_order_frontend_index', $this->registry->getRouteName('embedded_orders'));
    }

    public function testGetLayoutBlockIdsReturnsFragmentLayoutBlockIds(): void
    {
        self::assertSame(['order_grid', 'order_filters'], $this->registry->getLayoutBlockIds('embedded_orders'));
    }

    public function testGetRouteNameThrowsExceptionForUnknownFragment(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown embedded fragment "embedded_unknown".');

        $this->registry->getRouteName('embedded_unknown');
    }

    public function testGetLayoutBlockIdsThrowsExceptionForUnknownFragment(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown embedded fragment "embedded_unknown".');

        $this->registry->getLayoutBlockIds('embedded_unknown');
    }
}
