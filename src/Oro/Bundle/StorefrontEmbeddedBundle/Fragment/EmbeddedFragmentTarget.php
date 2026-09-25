<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Fragment;

/**
 * The storefront page an embedded block is served from: the route the block map names for the block, the url
 * the consumer's parameters generate for that route, and what the router matched for that url.
 */
class EmbeddedFragmentTarget
{
    /**
     * @param array<string, mixed>     $attributes      request attributes the router matched for the target path
     * @param array<string|int, mixed> $routeParameters query parameters the consumer passed for the target route
     */
    public function __construct(
        public readonly string $block,
        public readonly string $routeName,
        public readonly string $url,
        public readonly string $path,
        public readonly array $attributes,
        public readonly array $routeParameters,
    ) {
    }
}
