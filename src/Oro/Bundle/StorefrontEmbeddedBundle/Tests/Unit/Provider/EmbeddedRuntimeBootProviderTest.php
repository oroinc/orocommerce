<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Tests\Unit\Provider;

use Oro\Bundle\AssetBundle\Provider\SubresourceIntegrityProvider;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\StorefrontEmbeddedBundle\Provider\EmbeddedRuntimeBootProvider;
use Oro\Component\Layout\Extension\Theme\DataProvider\ThemeProvider;
use Oro\Component\Layout\Extension\Theme\Model\CurrentThemeProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Asset\Packages;

final class EmbeddedRuntimeBootProviderTest extends TestCase
{
    private ThemeProvider&MockObject $themeProvider;
    private CurrentThemeProvider&MockObject $currentThemeProvider;
    private Packages&MockObject $assetPackages;
    private SubresourceIntegrityProvider&MockObject $integrityProvider;
    private FeatureChecker&MockObject $featureChecker;
    private EmbeddedRuntimeBootProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->themeProvider = $this->createMock(ThemeProvider::class);
        $this->currentThemeProvider = $this->createMock(CurrentThemeProvider::class);
        $this->assetPackages = $this->createMock(Packages::class);
        $this->integrityProvider = $this->createMock(SubresourceIntegrityProvider::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->provider = new EmbeddedRuntimeBootProvider(
            $this->themeProvider,
            $this->currentThemeProvider,
            $this->assetPackages,
            $this->integrityProvider,
            $this->featureChecker
        );
    }

    public function testReturnsRootRelativeAssetUrlsOfTheCurrentThemeBuild(): void
    {
        $this->currentThemeProvider
            ->method('getCurrentThemeId')
            ->willReturn('intelligent_blue');
        $this->assetPackages
            ->method('getUrl')
            ->willReturnCallback(static fn (string $path): string => $path . '?v=abc123');
        $this->themeProvider
            ->method('getStylesOutput')
            ->willReturnMap([
                ['intelligent_blue', 'critical_css', 'build/intelligent_blue/css/critical.css'],
                ['intelligent_blue', 'styles', 'build/intelligent_blue/css/styles.css'],
                ['intelligent_blue', 'print_styles', 'build/intelligent_blue/css/print.css'],
            ]);
        $this->featureChecker
            ->method('isFeatureEnabled')
            ->with('asset_subresource_integrity_enabled')
            ->willReturn(false);
        $this->integrityProvider
            ->expects(self::never())
            ->method('getHash');

        self::assertSame(
            [
                'runtimeUrl' => '/build/intelligent_blue/embedded_app.js?v=abc123',
                'runtimeIntegrity' => '',
                'styles' => [
                    '/build/intelligent_blue/css/critical.css?v=abc123',
                    '/build/intelligent_blue/css/styles.css?v=abc123',
                ],
                'printStyles' => ['/build/intelligent_blue/css/print.css?v=abc123'],
            ],
            $this->provider->getBootMetadata()
        );
    }

    public function testReturnsTheRuntimeUrlThatTheAssetPackagesResolve(): void
    {
        $this->currentThemeProvider
            ->method('getCurrentThemeId')
            ->willReturn('intelligent_blue');
        $this->assetPackages
            ->method('getUrl')
            ->willReturnMap([
                ['/build/intelligent_blue/embedded_app.js', null, 'https://cdn.example.test/embedded_app.js?v=abc123'],
            ]);

        self::assertSame(
            'https://cdn.example.test/embedded_app.js?v=abc123',
            $this->provider->getBootMetadata()['runtimeUrl']
        );
    }

    public function testReturnsTheRuntimeIntegrityHashWhenSubresourceIntegrityIsEnabled(): void
    {
        $this->currentThemeProvider
            ->method('getCurrentThemeId')
            ->willReturn('intelligent_blue');
        $this->assetPackages
            ->method('getUrl')
            ->willReturnCallback(static fn (string $path): string => $path . '?v=abc123');
        $this->featureChecker
            ->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('asset_subresource_integrity_enabled')
            ->willReturn(true);
        $this->integrityProvider
            ->expects(self::once())
            ->method('getHash')
            ->with('/build/intelligent_blue/embedded_app.js')
            ->willReturn('sha384-hash');

        self::assertSame('sha384-hash', $this->provider->getBootMetadata()['runtimeIntegrity']);
    }

    public function testReturnsNoRuntimeIntegrityHashWhenTheRuntimeScriptHasNoHash(): void
    {
        $this->currentThemeProvider
            ->method('getCurrentThemeId')
            ->willReturn('intelligent_blue');
        $this->assetPackages
            ->method('getUrl')
            ->willReturnCallback(static fn (string $path): string => $path . '?v=abc123');
        $this->featureChecker
            ->method('isFeatureEnabled')
            ->with('asset_subresource_integrity_enabled')
            ->willReturn(true);
        $this->integrityProvider
            ->expects(self::once())
            ->method('getHash')
            ->willReturn(null);

        self::assertSame('', $this->provider->getBootMetadata()['runtimeIntegrity']);
    }

    public function testThrowsAnExceptionWhenThereIsNoCurrentTheme(): void
    {
        $this->currentThemeProvider
            ->method('getCurrentThemeId')
            ->willReturn(null);
        $this->featureChecker
            ->expects(self::never())
            ->method('isFeatureEnabled');
        $this->themeProvider
            ->expects(self::never())
            ->method('getStylesOutput');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The embedded runtime cannot boot because there is no current theme.');

        $this->provider->getBootMetadata();
    }
}
