<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Tests\Unit\Request;

use Oro\Bundle\StorefrontEmbeddedBundle\Event\EmbeddedFragmentResolveEvent;
use Oro\Bundle\StorefrontEmbeddedBundle\Request\EmbeddedFragmentRegistry;
use Oro\Bundle\StorefrontEmbeddedBundle\Request\EmbeddedFragmentUrlResolver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\InvalidParameterException;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class EmbeddedFragmentUrlResolverTest extends TestCase
{
    private RouterInterface&MockObject $router;

    private EventDispatcherInterface&MockObject $eventDispatcher;

    private EmbeddedFragmentUrlResolver $resolver;

    #[\Override]
    protected function setUp(): void
    {
        $this->router = $this->createMock(RouterInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->resolver = new EmbeddedFragmentUrlResolver(
            $this->router,
            new EmbeddedFragmentRegistry(
                [
                    'embedded_profile' => [
                        'route_name' => 'oro_customer_frontend_customer_user_profile',
                        'layout_block_ids' => ['embedded_profile'],
                    ],
                    'embedded_shopping_list_view' => [
                        'route_name' => 'oro_shopping_list_frontend_view',
                        'layout_block_ids' => ['embedded_shopping_list_view'],
                    ],
                    'embedded_customer_user_role_view' => [
                        'route_name' => 'oro_customer_frontend_customer_user_role_view',
                        'layout_block_ids' => ['embedded_customer_user_role_view'],
                    ],
                    'embedded_orders' => [
                        'route_name' => 'oro_order_frontend_index',
                        'layout_block_ids' => ['order_grid', 'order_filters'],
                    ],
                ]
            ),
            $this->eventDispatcher
        );
    }

    public function testThrowsNotFoundForUnknownFragment(): void
    {
        $this->eventDispatcher
            ->expects(self::never())
            ->method('dispatch');
        $this->router
            ->expects(self::never())
            ->method('generate');

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unknown embedded fragment.');

        $this->resolver->resolveFragmentUrl('embedded_unknown');
    }

    /**
     * @dataProvider throwsBadRequestWhenParametersDoNotFitTargetRouteProvider
     */
    public function testThrowsBadRequestWhenParametersDoNotFitTargetRoute(\Throwable $generateException): void
    {
        $this->router
            ->expects(self::once())
            ->method('generate')
            ->willThrowException($generateException);

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Invalid parameters for embedded fragment "embedded_customer_user_role_view".');

        $this->resolver->resolveFragmentUrl('embedded_customer_user_role_view');
    }

    /**
     * @return iterable<string, array{\Throwable}>
     */
    public static function throwsBadRequestWhenParametersDoNotFitTargetRouteProvider(): iterable
    {
        yield 'missing mandatory parameters' => [
            new MissingMandatoryParametersException('oro_customer_frontend_customer_user_role_view', ['id']),
        ];
        yield 'invalid parameter' => [new InvalidParameterException()];
    }

    public function testThrowsNotFoundWhenTargetRouteDoesNotExist(): void
    {
        $this->router
            ->expects(self::once())
            ->method('generate')
            ->willThrowException(new RouteNotFoundException());

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Embedded fragment "embedded_profile" points at an unknown route.');

        $this->resolver->resolveFragmentUrl('embedded_profile');
    }

    public function testDispatchesEventWithFragmentRouteBlocksParametersAndContext(): void
    {
        $request = Request::create('/embedded/fragment/embedded_orders');

        $this->eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(self::callback(static function (EmbeddedFragmentResolveEvent $event) use ($request): bool {
                self::assertSame('embedded_orders', $event->getFragmentName());
                self::assertSame('oro_order_frontend_index', $event->getRouteName());
                self::assertSame(['order_grid', 'order_filters'], $event->getBlockIds());
                self::assertSame(['page' => '2'], $event->getRouteParameters());
                self::assertSame([EmbeddedFragmentResolveEvent::CONTEXT_REQUEST => $request], $event->getContext());

                return true;
            }));
        $this->router
            ->method('generate')
            ->willReturn('/customer/order?page=2');

        $this->resolver->resolveFragmentUrl(
            'embedded_orders',
            ['page' => '2'],
            [EmbeddedFragmentResolveEvent::CONTEXT_REQUEST => $request]
        );
    }

    public function testGeneratesTargetUrlWithConfiguredBlocks(): void
    {
        $this->router
            ->expects(self::once())
            ->method('generate')
            ->with(
                'oro_shopping_list_frontend_view',
                [
                    'id' => '42',
                    'grid' => ['a' => 'b'],
                    0 => 'positional',
                    'layout_block_ids' => ['embedded_shopping_list_view'],
                ]
            )
            ->willReturn('/customer/shoppinglist/42?grid%5Ba%5D=b&0=positional');

        self::assertSame(
            '/customer/shoppinglist/42?grid%5Ba%5D=b&0=positional',
            $this->resolver->resolveFragmentUrl(
                'embedded_shopping_list_view',
                ['id' => '42', 'grid' => ['a' => 'b'], 0 => 'positional']
            )
        );
    }

    public function testGeneratesTargetUrlWithBlocksAndParametersSetByListeners(): void
    {
        $this->eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->willReturnCallback(static function (EmbeddedFragmentResolveEvent $event): EmbeddedFragmentResolveEvent {
                $event->setBlockIds(['order_grid']);
                $event->addBlockId('title');
                $event->setRouteParameters(['page' => '3', 'sort' => 'date']);

                return $event;
            });
        $this->router
            ->expects(self::once())
            ->method('generate')
            ->with(
                'oro_order_frontend_index',
                ['page' => '3', 'sort' => 'date', 'layout_block_ids' => ['order_grid', 'title']]
            )
            ->willReturn('/customer/order?page=3&sort=date');

        self::assertSame(
            '/customer/order?page=3&sort=date',
            $this->resolver->resolveFragmentUrl('embedded_orders', ['page' => '2'])
        );
    }

    public function testReplacesLayoutBlockIdsSentByCaller(): void
    {
        $this->router
            ->expects(self::once())
            ->method('generate')
            ->with('oro_customer_frontend_customer_user_profile', ['layout_block_ids' => ['embedded_profile']])
            ->willReturn('/customer/profile');

        self::assertSame(
            '/customer/profile',
            $this->resolver->resolveFragmentUrl('embedded_profile', ['layout_block_ids' => ['page_main_content']])
        );
    }

    public function testThrowsLogicExceptionWhenListenersLeaveNoBlockIds(): void
    {
        $this->eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->willReturnCallback(static function (EmbeddedFragmentResolveEvent $event): EmbeddedFragmentResolveEvent {
                $event->setBlockIds([]);

                return $event;
            });
        $this->router
            ->expects(self::never())
            ->method('generate');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'The listeners left the embedded fragment "embedded_shopping_list_view" without layout block ids.'
        );

        $this->resolver->resolveFragmentUrl('embedded_shopping_list_view', ['id' => '42']);
    }
}
