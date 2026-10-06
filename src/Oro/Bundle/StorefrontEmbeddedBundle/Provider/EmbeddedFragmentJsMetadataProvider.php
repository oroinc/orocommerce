<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Provider;

use Oro\Bundle\AssetBundle\Provider\SubresourceIntegrityProvider;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Component\Layout\Extension\Theme\Model\CurrentThemeProvider;
use Symfony\Component\Asset\Packages;

/**
 * Provides the JS runtime metadata required to render an embedded fragment.
 */
class EmbeddedFragmentJsMetadataProvider
{
    public function __construct(
        private readonly CurrentThemeProvider $currentThemeProvider,
        private readonly Packages $assetPackages,
        private readonly SubresourceIntegrityProvider $integrityProvider,
        private readonly FeatureChecker $featureChecker,
    ) {
    }

    /**
     * @return array{runtimeUrl: string, runtimeIntegrity: string}
     *
     * @throws \LogicException if there is no current theme
     */
    public function getJsMetadata(): array
    {
        $themeName = $this->currentThemeProvider->getCurrentThemeId();
        if (!$themeName) {
            throw new \LogicException('The embedded runtime cannot boot because there is no current theme.');
        }

        $runtimePath = sprintf('/build/%s/embedded_app.js', $themeName);

        return [
            'runtimeUrl' => $this->assetPackages->getUrl($runtimePath),
            'runtimeIntegrity' => $this->featureChecker->isFeatureEnabled('asset_subresource_integrity_enabled')
                ? (string) $this->integrityProvider->getHash($runtimePath)
                : '',
        ];
    }
}
