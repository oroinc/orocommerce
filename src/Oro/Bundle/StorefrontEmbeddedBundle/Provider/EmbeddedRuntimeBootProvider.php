<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Provider;

use Oro\Bundle\AssetBundle\Provider\SubresourceIntegrityProvider;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\StorefrontEmbeddedBundle\Request\EmbeddedRequestChecker;
use Oro\Component\Layout\Extension\Theme\DataProvider\ThemeProvider;
use Oro\Component\Layout\Extension\Theme\Model\CurrentThemeProvider;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\Request;

/**
 * Collects what the embedded runtime needs once to boot inside a consumer page: the theme build base, the
 * runtime script with its integrity hash, the stylesheets to load, the Oro routes whose client urls the
 * consumer has to proxy, and the path prefixes that answer with a file rather than a page. Every url is
 * left relative when the consumer serves that path itself, and made absolute against this host otherwise.
 */
class EmbeddedRuntimeBootProvider
{
    private const string BUILD_BASE_KEY = 'buildBase';
    private const string RUNTIME_URL_KEY = 'runtimeUrl';
    private const string RUNTIME_INTEGRITY_KEY = 'runtimeIntegrity';
    private const string STYLES_KEY = 'styles';
    private const string PRINT_STYLES_KEY = 'printStyles';
    private const string PROXIED_ROUTES_KEY = 'proxiedRoutes';
    private const string FILE_PATHS_KEY = 'filePaths';
    private const string CRITICAL_STYLES_SECTION = 'critical_css';
    private const string PRINT_STYLES_SECTION = 'print_styles';
    private const string THEME_BUILD_PATH_FORMAT = '/build/%s/';
    private const string RUNTIME_SCRIPT_FORMAT = '/build/%s/embedded_app.js';
    private const string INTEGRITY_FEATURE = 'asset_subresource_integrity_enabled';
    private const string VERSIONED_PATH_FORMAT = '%s?v=%s';
    private const string PATH_SEPARATOR = '/';

    public function __construct(
        private readonly ThemeProvider $themeProvider,
        private readonly CurrentThemeProvider $currentThemeProvider,
        private readonly Packages $assetPackages,
        private readonly EmbeddedRequestChecker $embeddedRequestChecker,
        private readonly SubresourceIntegrityProvider $integrityProvider,
        private readonly FeatureChecker $featureChecker,
        private readonly EmbeddedRoutePathsProvider $routePathsProvider,
    ) {
    }

    /**
     * @return array{
     *     buildBase: string,
     *     runtimeUrl: string,
     *     runtimeIntegrity: string,
     *     styles: string[],
     *     printStyles: string[],
     *     proxiedRoutes: string[],
     *     filePaths: string[]
     * }
     */
    public function getBootMetadata(Request $request, string $block): array
    {
        $routeMetadata = [
            self::PROXIED_ROUTES_KEY => $this->routePathsProvider->getProxiedRoutes($block),
            self::FILE_PATHS_KEY => $this->routePathsProvider->getFilePaths($block),
        ];

        $themeName = $this->currentThemeProvider->getCurrentThemeId();
        if (!$themeName) {
            return [
                self::BUILD_BASE_KEY => '',
                self::RUNTIME_URL_KEY => '',
                self::RUNTIME_INTEGRITY_KEY => '',
                self::STYLES_KEY => [],
                self::PRINT_STYLES_KEY => [],
            ] + $routeMetadata;
        }

        return [
            self::BUILD_BASE_KEY => $this->resolveBuildBase($request, $themeName),
            self::RUNTIME_URL_KEY => $this->resolveRuntimeUrl($request, $themeName),
            self::RUNTIME_INTEGRITY_KEY => $this->resolveRuntimeIntegrity($themeName),
            self::STYLES_KEY => $this->resolveStyleUrls($themeName),
            self::PRINT_STYLES_KEY => $this->resolvePrintStyleUrls($themeName),
        ] + $routeMetadata;
    }

    private function resolveBuildBase(Request $request, string $themeName): string
    {
        return $this->resolveUrlOnHostOrOro($request, sprintf(self::THEME_BUILD_PATH_FORMAT, $themeName));
    }

    private function resolveRuntimeUrl(Request $request, string $themeName): string
    {
        return $this->resolveUrlOnHostOrOro(
            $request,
            $this->withAssetVersion(sprintf(self::RUNTIME_SCRIPT_FORMAT, $themeName))
        );
    }

    private function resolveRuntimeIntegrity(string $themeName): string
    {
        if (
            !$this->isThemeBuildServedByHost($themeName)
            || !$this->featureChecker->isFeatureEnabled(self::INTEGRITY_FEATURE)
        ) {
            return '';
        }

        return (string) $this->integrityProvider->getHash(sprintf(self::RUNTIME_SCRIPT_FORMAT, $themeName));
    }

    private function isThemeBuildServedByHost(string $themeName): bool
    {
        return $this->embeddedRequestChecker
            ->isServedByHost(sprintf(self::THEME_BUILD_PATH_FORMAT, $themeName));
    }

    private function resolveUrlOnHostOrOro(Request $request, string $path): string
    {
        return $this->embeddedRequestChecker->isServedByHost($path)
            ? $path
            : $request->getSchemeAndHttpHost() . $path;
    }

    /**
     * @return string[]
     */
    private function resolveStyleUrls(string $themeName): array
    {
        return $this->asVersionedPaths([
            $this->themeProvider->getStylesOutput($themeName, self::CRITICAL_STYLES_SECTION),
            $this->themeProvider->getStylesOutput($themeName),
        ]);
    }

    /**
     * @return string[]
     */
    private function resolvePrintStyleUrls(string $themeName): array
    {
        return $this->asVersionedPaths([
            $this->themeProvider->getStylesOutput($themeName, self::PRINT_STYLES_SECTION),
        ]);
    }

    /**
     * @param array<int, string|null> $urls
     *
     * @return string[]
     */
    private function asVersionedPaths(array $urls): array
    {
        return array_values(array_map(
            fn (string $url): string => $this->withAssetVersion(
                str_starts_with($url, self::PATH_SEPARATOR) ? $url : self::PATH_SEPARATOR . $url
            ),
            array_filter($urls)
        ));
    }

    private function withAssetVersion(string $path): string
    {
        $version = $this->assetPackages->getVersion($path);

        return $version === '' ? $path : sprintf(self::VERSIONED_PATH_FORMAT, $path, $version);
    }
}
