<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Provider;

use Oro\Component\Layout\Extension\Theme\DataProvider\ThemeProvider;
use Oro\Component\Layout\Extension\Theme\Model\CurrentThemeProvider;
use Symfony\Component\Asset\Packages;

/**
 * Provides the styles metadata required to render an embedded fragment.
 */
class EmbeddedFragmentStylesMetadataProvider
{
    public function __construct(
        private readonly ThemeProvider $themeProvider,
        private readonly CurrentThemeProvider $currentThemeProvider,
        private readonly Packages $assetPackages,
    ) {
    }

    /**
     * @return array{styles: string[], printStyles: string[]}
     *
     * @throws \LogicException if there is no current theme
     */
    public function getStylesMetadata(): array
    {
        $themeName = $this->currentThemeProvider->getCurrentThemeId();
        if (!$themeName) {
            throw new \LogicException('The embedded runtime cannot boot because there is no current theme.');
        }

        return [
            'styles' => $this->asAssetUrls([
                $this->themeProvider->getStylesOutput($themeName, 'critical_css'),
                $this->themeProvider->getStylesOutput($themeName),
            ]),
            'printStyles' => $this->asAssetUrls([
                $this->themeProvider->getStylesOutput($themeName, 'print_styles'),
            ]),
        ];
    }

    /**
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
