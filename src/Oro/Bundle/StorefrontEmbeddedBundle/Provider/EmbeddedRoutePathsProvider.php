<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Provider;

use Oro\Component\Config\Cache\PhpArrayConfigProvider;
use Oro\Component\Config\ResourcesContainerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\Routing\RouterInterface;

/**
 * Serves the route-derived part of the embedded boot metadata: per embedded block, the Oro routes whose client
 * urls the consumer has to proxy, and the path prefixes of the routes that answer with a file rather than a page,
 * which is all the runtime needs to spot one. The prefixes are read from the route collection once, at cache
 * warm-up, because building the whole collection is far too expensive for a request.
 */
class EmbeddedRoutePathsProvider extends PhpArrayConfigProvider implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public const string ANY_BLOCK = 'any_block';

    private const string ORIGIN_ROOT = '/';
    private const string ROUTE_PARAMETER_START = '{';

    /**
     * @param array<string, string[]> $proxiedRoutesPerBlock embedded block id => routes the consumer proxies
     * @param array<string, string[]> $fileRoutesPerBlock    embedded block id => routes that answer with a file
     */
    public function __construct(
        string $cacheFile,
        bool $debug,
        private readonly RouterInterface $router,
        private readonly array $proxiedRoutesPerBlock,
        private readonly array $fileRoutesPerBlock,
    ) {
        parent::__construct($cacheFile, $debug);
        $this->logger = new NullLogger();
    }

    /**
     * @return string[] the routes whose client urls the consumer proxies for the block
     */
    public function getProxiedRoutes(string $block): array
    {
        return array_values($this->proxiedRoutesPerBlock[$block] ?? []);
    }

    /**
     * @return string[] the path each file route of the block starts with
     */
    public function getFilePaths(string $block): array
    {
        $pathPrefixes = $this->doGetConfig();

        $filePaths = [];
        foreach ($this->resolveFileRouteNames($block) as $routeName) {
            $filePath = $pathPrefixes[$routeName] ?? null;
            if (null !== $filePath && $filePath !== self::ORIGIN_ROOT) {
                $filePaths[] = $filePath;
            }
        }

        return array_values(array_unique($filePaths));
    }

    /**
     * @return array<string, string> file route name => the path the route starts with
     */
    #[\Override]
    protected function doLoadConfig(ResourcesContainerInterface $resourcesContainer): array
    {
        $routes = $this->router->getRouteCollection();
        foreach ($routes->getResources() as $resource) {
            $resourcesContainer->addResource($resource);
        }

        $pathPrefixes = [];
        foreach ($this->fileRoutesPerBlock as $block => $routeNames) {
            foreach ($routeNames as $routeName) {
                $route = $routes->get($routeName);
                if (null === $route) {
                    $this->logger->warning('Embedded file route is not defined and was skipped.', [
                        'route' => $routeName,
                        'block' => $block,
                    ]);
                    continue;
                }

                $pathPrefixes[$routeName] = $this->resolvePathBeforeTheFirstParameter($route->getPath());
            }
        }

        return $pathPrefixes;
    }

    /**
     * @return string[]
     */
    private function resolveFileRouteNames(string $block): array
    {
        return array_merge(
            $this->fileRoutesPerBlock[self::ANY_BLOCK] ?? [],
            $this->fileRoutesPerBlock[$block] ?? []
        );
    }

    private function resolvePathBeforeTheFirstParameter(string $routePath): string
    {
        $parameterAt = strpos($routePath, self::ROUTE_PARAMETER_START);

        return $parameterAt === false ? $routePath : substr($routePath, 0, $parameterAt);
    }
}
