<?php

namespace Oro\Bundle\StorefrontEmbeddedBundle\Provider;

use Oro\Bundle\AssetBundle\Provider\SubresourceIntegrityProvider;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\StorefrontEmbeddedBundle\Request\EmbeddedRequestChecker;
use Oro\Component\Layout\Extension\Theme\DataProvider\ThemeProvider;
use Oro\Component\Layout\Extension\Theme\Model\CurrentThemeProvider;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;

/**
 * Collects what the embedded runtime needs once to boot inside a consumer page: the theme build base, the
 * runtime script with its integrity hash, the stylesheets to load, the Oro routes whose client urls the
 * consumer has to proxy, and the path prefixes that answer with a file rather than a page. Every url is
 * left relative when the consumer serves that path itself, and made absolute against this host otherwise.
 */
class EmbeddedRuntimeBootProvider
{
    private const string CRITICAL_STYLES_SECTION = 'critical_css';
    private const string PRINT_STYLES_SECTION = 'print_styles';
    private const string STYLES_KEY = 'styles';
    private const string PRINT_STYLES_KEY = 'printStyles';
    private const string PROXIED_ROUTES_KEY = 'proxiedRoutes';
    private const string FILE_PATHS_KEY = 'filePaths';
    private const string ANY_BLOCK = 'any_block';
    private const string ORIGIN_ROOT = '/';
    private const string BUILD_BASE_KEY = 'buildBase';
    private const string RUNTIME_URL_KEY = 'runtimeUrl';
    private const string RUNTIME_INTEGRITY_KEY = 'runtimeIntegrity';
    private const string THEME_BUILD_PATH_FORMAT = '/build/%s/';
    private const string RUNTIME_SCRIPT_FORMAT = '/build/%s/embedded_app.js';
    private const string INTEGRITY_FEATURE = 'asset_subresource_integrity_enabled';
    private const string VERSIONED_PATH_FORMAT = '%s?v=%s';

    /**
     * @param array<string, string[]> $proxiedRoutesPerBlock embedded block id => routes the consumer proxies
     * @param array<string, string[]> $fileRoutesPerBlock    embedded block id => routes that answer with a file
     */
    public function __construct(
        private readonly ThemeProvider $themeProvider,
        private readonly CurrentThemeProvider $currentThemeProvider,
        private readonly RouterInterface $router,
        private readonly Packages $assetPackages,
        private readonly EmbeddedRequestChecker $embeddedRequestChecker,
        private readonly SubresourceIntegrityProvider $integrityProvider,
        private readonly FeatureChecker $featureChecker,
        private readonly array $proxiedRoutesPerBlock,
        private readonly array $fileRoutesPerBlock,
    ) {
    }

    public function getBootMetadata(Request $request, string $block): array
    {
        $themeName = $this->currentThemeProvider->getCurrentThemeId();

        return [
            self::BUILD_BASE_KEY => $themeName ? $this->buildBase($request, $themeName) : '',
            self::RUNTIME_URL_KEY => $themeName ? $this->runtimeUrl($request, $themeName) : '',
            self::RUNTIME_INTEGRITY_KEY => $themeName ? $this->runtimeIntegrity($themeName) : '',
            self::STYLES_KEY => $this->resolveStyleUrls($themeName),
            self::PRINT_STYLES_KEY => $this->resolvePrintStyleUrls($themeName),
            self::PROXIED_ROUTES_KEY => array_values($this->proxiedRoutesPerBlock[$block] ?? []),
            self::FILE_PATHS_KEY => $this->filePathsOf($block),
        ];
    }

    private function buildBase(Request $request, string $themeName): string
    {
        return $this->urlOnHostOrOro($request, sprintf(self::THEME_BUILD_PATH_FORMAT, $themeName));
    }

    private function runtimeUrl(Request $request, string $themeName): string
    {
        return $this->urlOnHostOrOro(
            $request,
            $this->withAssetVersion(sprintf(self::RUNTIME_SCRIPT_FORMAT, $themeName))
        );
    }

    private function runtimeIntegrity(string $themeName): string
    {
        if (
            !$this->themeBuildServedByHost($themeName)
            || !$this->featureChecker->isFeatureEnabled(self::INTEGRITY_FEATURE)
        ) {
            return '';
        }

        return (string) $this->integrityProvider->getHash(sprintf(self::RUNTIME_SCRIPT_FORMAT, $themeName));
    }

    private function themeBuildServedByHost(string $themeName): bool
    {
        return $this->embeddedRequestChecker
            ->isServedByHost(sprintf(self::THEME_BUILD_PATH_FORMAT, $themeName));
    }

    private function urlOnHostOrOro(Request $request, string $path): string
    {
        return $this->embeddedRequestChecker->isServedByHost($path)
            ? $path
            : $request->getSchemeAndHttpHost() . $path;
    }

    private function resolveStyleUrls(?string $themeName): array
    {
        if (!$themeName) {
            return [];
        }

        return $this->asVersionedPaths([
            $this->themeProvider->getStylesOutput($themeName, self::CRITICAL_STYLES_SECTION),
            $this->themeProvider->getStylesOutput($themeName),
        ]);
    }

    private function resolvePrintStyleUrls(?string $themeName): array
    {
        if (!$themeName) {
            return [];
        }

        return $this->asVersionedPaths([
            $this->themeProvider->getStylesOutput($themeName, self::PRINT_STYLES_SECTION),
        ]);
    }

    private function asVersionedPaths(array $urls): array
    {
        return array_values(array_map(
            fn (string $url): string => $this->withAssetVersion(str_starts_with($url, '/') ? $url : '/' . $url),
            array_filter($urls)
        ));
    }

    private function withAssetVersion(string $path): string
    {
        $version = $this->assetPackages->getVersion($path);

        return $version === '' ? $path : sprintf(self::VERSIONED_PATH_FORMAT, $path, $version);
    }

    /**
     * @return string[] the path each file route starts with, which is all the runtime needs to spot one
     */
    private function filePathsOf(string $block): array
    {
        $routeNames = array_merge(
            $this->fileRoutesPerBlock[self::ANY_BLOCK] ?? [],
            $this->fileRoutesPerBlock[$block] ?? []
        );
        $routes = $this->router->getRouteCollection();

        $filePaths = [];
        foreach ($routeNames as $routeName) {
            $route = $routes->get($routeName);
            if (!$route) {
                continue;
            }

            $filePath = $this->pathBeforeTheFirstParameter($route->getPath());
            if ($filePath !== self::ORIGIN_ROOT) {
                $filePaths[] = $filePath;
            }
        }

        return array_values(array_unique($filePaths));
    }

    private function pathBeforeTheFirstParameter(string $routePath): string
    {
        $parameterAt = strpos($routePath, '{');

        return $parameterAt === false ? $routePath : substr($routePath, 0, $parameterAt);
    }
}
