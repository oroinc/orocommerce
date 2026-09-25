<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Tests\Unit\Provider;

use Oro\Bundle\StorefrontEmbeddedBundle\Provider\EmbeddedRoutePathsProvider;
use Oro\Component\Config\ResourcesContainerInterface;
use Oro\Component\Testing\TempDirExtension;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

final class EmbeddedRoutePathsProviderTest extends TestCase
{
    use TempDirExtension;

    private RouterInterface&MockObject $router;

    #[\Override]
    protected function setUp(): void
    {
        $this->router = $this->createMock(RouterInterface::class);
    }

    public function testGetProxiedRoutesReturnsTheConfiguredRoutesForAKnownBlock(): void
    {
        $provider = new EmbeddedRoutePathsProvider(
            $this->getTempFile('embedded_route_paths'),
            false,
            $this->router,
            ['embedded_shopping_list' => ['oro_pricing_frontend_price_by_customer']],
            []
        );

        self::assertSame(
            ['oro_pricing_frontend_price_by_customer'],
            $provider->getProxiedRoutes('embedded_shopping_list')
        );
    }

    public function testGetProxiedRoutesReturnsEmptyArrayForAnUnknownBlock(): void
    {
        $provider = new EmbeddedRoutePathsProvider($this->getTempFile('embedded_route_paths'), false, $this->router, [], []);

        self::assertSame([], $provider->getProxiedRoutes('embedded_unknown'));
    }

    public function testLoadConfigAddsTheRouteCollectionResourcesToTheResourcesContainer(): void
    {
        $collection = new RouteCollection();
        $resource = new FileResource(__FILE__);
        $collection->addResource($resource);
        $this->router->method('getRouteCollection')
            ->willReturn($collection);

        $provider = new EmbeddedRoutePathsProvider($this->getTempFile('embedded_route_paths'), false, $this->router, [], []);

        $resourcesContainer = $this->createMock(ResourcesContainerInterface::class);
        $resourcesContainer->expects(self::once())
            ->method('addResource')
            ->with($resource);

        self::assertSame([], $provider->loadConfig($resourcesContainer));
    }

    public function testLoadConfigBuildsThePathBeforeTheFirstRouteParameterForEachFileRoute(): void
    {
        $collection = new RouteCollection();
        $collection->add('route_with_param', new Route('/catalog/{id}/download'));
        $collection->add('route_without_param', new Route('/export/all'));
        $this->router->method('getRouteCollection')
            ->willReturn($collection);

        $provider = new EmbeddedRoutePathsProvider(
            $this->getTempFile('embedded_route_paths'),
            false,
            $this->router,
            [],
            ['any_block' => ['route_with_param', 'route_without_param']]
        );

        self::assertSame(
            ['route_with_param' => '/catalog/', 'route_without_param' => '/export/all'],
            $provider->loadConfig($this->createMock(ResourcesContainerInterface::class))
        );
    }

    public function testLoadConfigSkipsAndLogsARouteNameTheCollectionDoesNotHave(): void
    {
        $this->router->method('getRouteCollection')
            ->willReturn(new RouteCollection());

        $provider = new EmbeddedRoutePathsProvider(
            $this->getTempFile('embedded_route_paths'),
            false,
            $this->router,
            [],
            ['embedded_x' => ['route_missing']]
        );

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('warning')
            ->with('Embedded file route is not defined and was skipped.', ['route' => 'route_missing', 'block' => 'embedded_x']);
        $provider->setLogger($logger);

        self::assertSame([], $provider->loadConfig($this->createMock(ResourcesContainerInterface::class)));
    }

    public function testGetFilePathsMergesAnyBlockAndTheBlocksOwnFileRoutesDeduplicatingAndDroppingRoot(): void
    {
        $collection = new RouteCollection();
        $collection->add('oro_frontend_attachment_get_file', new Route('/attachment/file/{id}'));
        $collection->add('oro_home', new Route('/'));
        $collection->add('oro_export', new Route('/export/all'));
        $this->router->method('getRouteCollection')
            ->willReturn($collection);

        $provider = new EmbeddedRoutePathsProvider(
            $this->getTempFile('embedded_route_paths'),
            false,
            $this->router,
            [],
            [
                'any_block' => ['oro_frontend_attachment_get_file', 'oro_home'],
                'embedded_x' => ['oro_frontend_attachment_get_file', 'oro_export'],
            ]
        );

        self::assertSame(['/attachment/file/', '/export/all'], $provider->getFilePaths('embedded_x'));
    }

    public function testGetFilePathsReturnsEmptyArrayForABlockWithNoFileRoutes(): void
    {
        $this->router->method('getRouteCollection')
            ->willReturn(new RouteCollection());

        $provider = new EmbeddedRoutePathsProvider(
            $this->getTempFile('embedded_route_paths'),
            false,
            $this->router,
            [],
            ['any_block' => []]
        );

        self::assertSame([], $provider->getFilePaths('embedded_unknown'));
    }
}
