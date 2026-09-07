<?php

namespace Oro\Bundle\StorefrontEmbeddedBundle\Request;

use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tells whether the storefront is rendering for an embedded host. The host marks every request it makes —
 * the fragment itself and the AJAX the fragment's content causes — with the X-Oro-Embedded header, because
 * a host page cannot serve Oro's own root-relative urls from its origin. It also names the prefix it serves
 * Oro through, so a url the browser has to read cross-origin can be pointed back at the host instead, and the
 * asset roots it serves from its own origin, which are the paths Oro must leave as they are.
 */
class EmbeddedRequestChecker
{
    public const string EMBEDDED_HEADER = 'X-Oro-Embedded';
    public const string PROXY_PREFIX_HEADER = 'X-Oro-Embedded-Proxy-Prefix';
    public const string ASSET_ROOTS_HEADER = 'X-Oro-Embedded-Asset-Roots';

    private const string FRAGMENT_ROUTE = 'oro_storefront_embedded_fragment';
    private const string HOST_PATH = '#^/[^/\s]\S*$#';

    public function __construct(private readonly RequestStack $requestStack)
    {
    }

    public function isEmbeddedRequest(): bool
    {
        return (bool) $this->requestStack->getMainRequest()?->headers->has(self::EMBEDDED_HEADER);
    }

    public function rendersFragment(): bool
    {
        return $this->requestStack->getMainRequest()?->attributes->get('_route') === self::FRAGMENT_ROUTE;
    }

    public function proxyPrefix(): string
    {
        $prefix = rtrim((string) $this->requestStack->getMainRequest()?->headers->get(self::PROXY_PREFIX_HEADER), '/');

        return preg_match(self::HOST_PATH, $prefix) ? $prefix : '';
    }

    public function isServedByHost(string $path): bool
    {
        foreach ($this->assetRootsServedByHost() as $root) {
            if (str_starts_with($path, $root . '/')) {
                return true;
            }
        }

        return false;
    }

    public function assetRootsServedByHost(): array
    {
        $header = (string) $this->requestStack->getMainRequest()?->headers->get(self::ASSET_ROOTS_HEADER);
        $roots = array_map(static fn (string $root): string => rtrim(trim($root), '/'), explode(',', $header));

        return array_values(
            array_filter($roots, static fn (string $root): bool => (bool) preg_match(self::HOST_PATH, $root))
        );
    }
}
