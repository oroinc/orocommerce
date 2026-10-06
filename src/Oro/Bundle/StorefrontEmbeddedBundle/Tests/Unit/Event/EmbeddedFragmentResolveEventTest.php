<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Tests\Unit\Event;

use Oro\Bundle\StorefrontEmbeddedBundle\Event\EmbeddedFragmentResolveEvent;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class EmbeddedFragmentResolveEventTest extends TestCase
{
    public function testGettersReturnConstructorArguments(): void
    {
        $request = Request::create('/embedded/fragment/embedded_orders');

        $event = new EmbeddedFragmentResolveEvent(
            'embedded_orders',
            'oro_order_frontend_index',
            ['page' => '2'],
            [EmbeddedFragmentResolveEvent::CONTEXT_REQUEST => $request],
            ['order_grid', 'order_filters']
        );

        self::assertSame('embedded_orders', $event->getFragmentName());
        self::assertSame('oro_order_frontend_index', $event->getRouteName());
        self::assertSame(['order_grid', 'order_filters'], $event->getBlockIds());
        self::assertSame(['page' => '2'], $event->getRouteParameters());
        self::assertSame([EmbeddedFragmentResolveEvent::CONTEXT_REQUEST => $request], $event->getContext());
        self::assertSame($request, $event->getContextValue(EmbeddedFragmentResolveEvent::CONTEXT_REQUEST));
    }

    public function testGetContextValueReturnsNullForMissingValue(): void
    {
        $event = new EmbeddedFragmentResolveEvent(
            'embedded_orders',
            'oro_order_frontend_index',
            [],
            [],
            ['order_grid']
        );

        self::assertNull($event->getContextValue(EmbeddedFragmentResolveEvent::CONTEXT_REQUEST));
    }

    public function testSetBlockIdsReplacesBlockIds(): void
    {
        $event = new EmbeddedFragmentResolveEvent(
            'embedded_orders',
            'oro_order_frontend_index',
            [],
            [],
            ['order_grid', 'order_filters']
        );

        $event->setBlockIds(['order_filters']);

        self::assertSame(['order_filters'], $event->getBlockIds());
    }

    public function testAddBlockIdAppendsBlockId(): void
    {
        $event = new EmbeddedFragmentResolveEvent(
            'embedded_orders',
            'oro_order_frontend_index',
            [],
            [],
            ['order_grid', 'order_filters']
        );

        $event->addBlockId('layout_js_modules_config');
        $event->addBlockId('title');

        self::assertSame(
            ['order_grid', 'order_filters', 'layout_js_modules_config', 'title'],
            $event->getBlockIds()
        );
    }

    public function testSetRouteParametersReplacesRouteParameters(): void
    {
        $event = new EmbeddedFragmentResolveEvent(
            'embedded_orders',
            'oro_order_frontend_index',
            ['page' => '2'],
            [],
            ['order_grid']
        );

        $event->setRouteParameters(['page' => '3', 'sort' => 'date']);

        self::assertSame(['page' => '3', 'sort' => 'date'], $event->getRouteParameters());
    }
}
