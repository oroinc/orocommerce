<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Request;

use Oro\Bundle\NavigationBundle\Event\ResponseHashnavListener;
use Oro\Bundle\StorefrontEmbeddedBundle\Controller\Frontend\EmbeddedFragmentController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Tells whether the storefront is rendering for an embedded host. The host marks every request it makes,
 * the fragment itself and the AJAX the fragment's content causes, with the X-Oro-Embedded header, because
 * a host page cannot serve Oro's own root-relative urls from its origin. It also names the prefix it serves
 * Oro through, so a url the browser has to read cross-origin can be pointed back at the host instead, and the
 * asset roots it serves from its own origin, which are the paths Oro must leave as they are. On the fragment
 * request itself the host may also name the localization and the currency the fragment is rendered with.
 */
class EmbeddedRequestChecker
{
    public const string EMBEDDED_HEADER = 'X-Oro-Embedded';
    public const string PROXY_PREFIX_HEADER = 'X-Oro-Embedded-Proxy-Prefix';
    public const string ASSET_ROOTS_HEADER = 'X-Oro-Embedded-Asset-Roots';
    public const string LOCALIZATION_ID_HEADER = 'X-Localization-ID';
    public const string CURRENCY_HEADER = 'X-Currency';

    private const string ROUTE_ATTRIBUTE = '_route';
    private const string ASSET_ROOTS_SEPARATOR = ',';
    private const string PATH_SEPARATOR = '/';
    // A root-relative path of plain segments: no other origin, no query, no fragment, no markup characters.
    private const string HOST_PATH_PATTERN = '#^/(?:[A-Za-z0-9._~-]+/?)+$#';

    public function __construct(private readonly RequestStack $requestStack)
    {
    }

    public function isEmbeddedRequest(): bool
    {
        return (bool) $this->requestStack->getMainRequest()?->headers->has(self::EMBEDDED_HEADER);
    }

    public function rendersFragment(): bool
    {
        return $this->requestStack->getMainRequest()?->attributes->get(self::ROUTE_ATTRIBUTE)
            === EmbeddedFragmentController::ROUTE_NAME;
    }

    /**
     * Tells whether the fragment request reloads an already booted fragment through hash navigation, which needs
     * neither the config scripts block nor the boot metadata again.
     */
    public function isFragmentReload(): bool
    {
        return (bool) $this->getMainRequestHeader(ResponseHashnavListener::HASH_NAVIGATION_HEADER);
    }

    public function getProxyPrefix(): string
    {
        $prefix = rtrim((string) $this->getMainRequestHeader(self::PROXY_PREFIX_HEADER), self::PATH_SEPARATOR);

        return preg_match(self::HOST_PATH_PATTERN, $prefix) ? $prefix : '';
    }

    public function isServedByHost(string $path): bool
    {
        foreach ($this->getAssetRootsServedByHost() as $root) {
            if (str_starts_with($path, $root . self::PATH_SEPARATOR)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return string[] root-relative paths the host serves from its own origin
     */
    public function getAssetRootsServedByHost(): array
    {
        $header = (string) $this->getMainRequestHeader(self::ASSET_ROOTS_HEADER);
        $roots = array_map(
            static fn (string $root): string => rtrim(trim($root), self::PATH_SEPARATOR),
            explode(self::ASSET_ROOTS_SEPARATOR, $header)
        );

        return array_values(
            array_filter($roots, static fn (string $root): bool => (bool) preg_match(self::HOST_PATH_PATTERN, $root))
        );
    }

    /**
     * Upgrades a root-relative reference type to an absolute one while rendering for an embedded host, which cannot
     * serve Oro's paths from its own origin. Any other reference type is what the caller asked for and stays.
     */
    public function resolveReferenceType(int $referenceType): int
    {
        if ($referenceType !== UrlGeneratorInterface::ABSOLUTE_PATH || !$this->isEmbeddedRequest()) {
            return $referenceType;
        }

        return UrlGeneratorInterface::ABSOLUTE_URL;
    }

    /**
     * Reads a header the host sends on the fragment request itself; null on any other request.
     */
    public function getFragmentRequestHeader(string $name): ?string
    {
        return $this->rendersFragment() ? $this->getMainRequestHeader($name) : null;
    }

    public function getMainRequestHeader(string $name): ?string
    {
        return $this->requestStack->getMainRequest()?->headers->get($name);
    }
}
