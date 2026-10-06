<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Provider;

use Oro\Bundle\AssetBundle\Provider\SubresourceIntegrityProvider;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Component\Layout\Extension\Theme\DataProvider\ThemeProvider;
use Oro\Component\Layout\Extension\Theme\Model\CurrentThemeProvider;
use Symfony\Component\Asset\Packages;

/**
 * Collects the data that the embedded runtime needs to boot in a consumer page.
 */
class EmbeddedRuntimeBootProvider
{
    public function __construct(
        private readonly ThemeProvider $themeProvider,
        private readonly CurrentThemeProvider $currentThemeProvider,
        private readonly Packages $assetPackages,
        private readonly SubresourceIntegrityProvider $integrityProvider,
        private readonly FeatureChecker $featureChecker,
    ) {
    }

    /**
     * @return array{
     *     runtimeUrl: string,
     *     runtimeIntegrity: string,
     *     styles: string[],
     *     printStyles: string[]
     * }
     *
     * @throws \LogicException if there is no current theme
     */
    public function getBootMetadata(): array
    {
        $themeName = $this->currentThemeProvider->getCurrentThemeId();
        if (!$themeName) {
            throw new \LogicException('The embedded runtime cannot boot because there is no current theme.');
        }

        return [
            'runtimeUrl' => $this->assetPackages->getUrl(sprintf('/build/%s/embedded_app.js', $themeName)),
            'runtimeIntegrity' => $this->resolveRuntimeIntegrity($themeName),
            'styles' => $this->resolveStyleUrls($themeName),
            'printStyles' => $this->resolvePrintStyleUrls($themeName),
        ];
    }

    private function resolveRuntimeIntegrity(string $themeName): string
    {
        if (!$this->featureChecker->isFeatureEnabled('asset_subresource_integrity_enabled')) {
            return '';
        }

        return (string)$this->integrityProvider->getHash(sprintf('/build/%s/embedded_app.js', $themeName));
    }

    /**
     * @return string[]
     */
    private function resolveStyleUrls(string $themeName): array
    {
        return $this->asAssetUrls([
            $this->themeProvider->getStylesOutput($themeName, 'critical_css'),
            $this->themeProvider->getStylesOutput($themeName),
        ]);
    }

    /**
     * @return string[]
     */
    private function resolvePrintStyleUrls(string $themeName): array
    {
        return $this->asAssetUrls([
            $this->themeProvider->getStylesOutput($themeName, 'print_styles'),
        ]);
    }

    /**
     * The leading slash keeps the paths root-relative: the asset package adds the request base path only to paths
     * without it.
     *
     * @param array<int, string|null> $paths
     *
     * @return string[]
     */
    private function asAssetUrls(array $paths): array
    {
        return array_values(
            array_map(
                fn (string $path): string => $this->assetPackages->getUrl('/' . $path),
                array_filter($paths)
            )
        );
    }
}
