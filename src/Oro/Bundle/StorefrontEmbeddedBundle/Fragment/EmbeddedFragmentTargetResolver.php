<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Fragment;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\InvalidParameterException;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\RouterInterface;

/**
 * Resolves an embedded block to the storefront page it is served from: the block map names the route, the
 * consumer's query parameters generate the page url, and matching that url back yields the request attributes
 * the target page runs with. Internal (underscore-prefixed) parameters never reach the url generator, and the
 * matched route has to be the configured one, so a colliding route cannot quietly re-point a block.
 */
class EmbeddedFragmentTargetResolver
{
    private const string ROUTE_ATTRIBUTE = '_route';
    private const string INTERNAL_PARAMETER_PREFIX = '_';

    /**
     * @param array<string, string> $embeddedBlocks embedded block id => route name of the page the block lives on
     */
    public function __construct(
        private readonly RouterInterface $router,
        private readonly array $embeddedBlocks,
    ) {
    }

    /**
     * @throws NotFoundHttpException   when the block is unknown or its page cannot be resolved
     * @throws BadRequestHttpException when the consumer's parameters do not fit the target route
     */
    public function resolve(string $block, Request $request): EmbeddedFragmentTarget
    {
        $routeName = $this->resolveRouteName($block);
        $routeParameters = $this->withoutInternalParameters($request->query->all());
        $url = $this->generateUrl($block, $routeName, $routeParameters);
        $path = $this->withoutBaseUrl((string) parse_url($url, PHP_URL_PATH));
        $attributes = $this->matchPath($block, $path);
        if (($attributes[self::ROUTE_ATTRIBUTE] ?? null) !== $routeName) {
            throw new NotFoundHttpException(
                sprintf('The url of embedded block "%s" is served by another route.', $block)
            );
        }

        return new EmbeddedFragmentTarget($block, $routeName, $url, $path, $attributes, $routeParameters);
    }

    private function resolveRouteName(string $block): string
    {
        $routeName = $this->embeddedBlocks[$block] ?? null;
        if (!$routeName) {
            throw new NotFoundHttpException(sprintf('Unknown embedded block "%s".', $block));
        }

        return $routeName;
    }

    /**
     * @param array<string|int, mixed> $queryParameters
     *
     * @return array<string|int, mixed>
     */
    private function withoutInternalParameters(array $queryParameters): array
    {
        return array_filter(
            $queryParameters,
            static fn (string|int $name): bool => !\is_string($name)
                || !str_starts_with($name, self::INTERNAL_PARAMETER_PREFIX),
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * @param array<string|int, mixed> $routeParameters
     */
    private function generateUrl(string $block, string $routeName, array $routeParameters): string
    {
        try {
            return $this->router->generate($routeName, $routeParameters);
        } catch (MissingMandatoryParametersException | InvalidParameterException $exception) {
            throw new BadRequestHttpException(
                sprintf('Invalid parameters for embedded block "%s".', $block),
                $exception
            );
        } catch (RouteNotFoundException $exception) {
            throw new NotFoundHttpException(
                sprintf('Embedded block "%s" points at an unknown route.', $block),
                $exception
            );
        }
    }

    private function withoutBaseUrl(string $path): string
    {
        $baseUrl = $this->router->getContext()->getBaseUrl();

        return $baseUrl !== '' && str_starts_with($path, $baseUrl) ? substr($path, \strlen($baseUrl)) : $path;
    }

    /**
     * @return array<string, mixed>
     */
    private function matchPath(string $block, string $path): array
    {
        try {
            return $this->router->match($path);
        } catch (ResourceNotFoundException | MethodNotAllowedException $exception) {
            throw new NotFoundHttpException(
                sprintf('Cannot resolve the target route of embedded block "%s".', $block),
                $exception
            );
        }
    }
}
