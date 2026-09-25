<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Asset;

use Oro\Bundle\StorefrontEmbeddedBundle\Request\EmbeddedRequestChecker;
use Symfony\Component\Asset\PackageInterface;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Serves the urls of Oro's own asset files as absolute Oro urls while rendering for an embedded host, so they are
 * loaded from Oro instead of from the host, which has no such files. The browser loads them as plain scripts,
 * stylesheets and images, so Oro's web server needs no Access-Control-Allow-Origin header for them. A host that
 * would rather serve some of those paths itself says so on the request; a url under such a root is left as the path
 * it always was.
 *
 * Extends Packages only to satisfy strict type-hints; every method is delegated.
 */
class EmbeddedRequestAssetPackages extends Packages
{
    private const string PATH_SEPARATOR = '/';
    private const string PROTOCOL_RELATIVE_PREFIX = '//';

    /**
     * @param string[] $oroOwnPaths root-relative prefixes of the asset urls Oro serves itself
     */
    public function __construct(
        private readonly Packages $innerPackages,
        private readonly RequestStack $requestStack,
        private readonly EmbeddedRequestChecker $embeddedRequestChecker,
        private readonly array $oroOwnPaths = ['/build/', '/bundles/'],
    ) {
    }

    #[\Override]
    public function getUrl(string $path, ?string $packageName = null): string
    {
        $url = $this->innerPackages->getUrl($path, $packageName);

        if (!$this->belongsToOro($url) || $this->embeddedRequestChecker->isServedByHost($url)) {
            return $url;
        }

        return $this->getOroOrigin() . $url;
    }

    #[\Override]
    public function getVersion(string $path, ?string $packageName = null): string
    {
        return $this->innerPackages->getVersion($path, $packageName);
    }

    #[\Override]
    public function getPackage(?string $name = null): PackageInterface
    {
        return $this->innerPackages->getPackage($name);
    }

    #[\Override]
    public function setDefaultPackage(PackageInterface $defaultPackage): void
    {
        $this->innerPackages->setDefaultPackage($defaultPackage);
    }

    #[\Override]
    public function addPackage(string $name, PackageInterface $package): void
    {
        $this->innerPackages->addPackage($name, $package);
    }

    private function belongsToOro(string $url): bool
    {
        if (!str_starts_with($url, self::PATH_SEPARATOR) || str_starts_with($url, self::PROTOCOL_RELATIVE_PREFIX)) {
            return false;
        }

        foreach ($this->oroOwnPaths as $path) {
            if (str_starts_with($url, $path)) {
                return $this->embeddedRequestChecker->isEmbeddedRequest();
            }
        }

        return false;
    }

    private function getOroOrigin(): string
    {
        return $this->requestStack->getMainRequest()?->getSchemeAndHttpHost() ?? '';
    }
}
