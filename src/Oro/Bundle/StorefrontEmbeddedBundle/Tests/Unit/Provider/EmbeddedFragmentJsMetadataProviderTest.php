<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Tests\Unit\Provider;

use Oro\Bundle\AssetBundle\Provider\SubresourceIntegrityProvider;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\StorefrontEmbeddedBundle\Provider\EmbeddedFragmentJsMetadataProvider;
use Oro\Component\Layout\Extension\Theme\Model\CurrentThemeProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Asset\Packages;

final class EmbeddedFragmentJsMetadataProviderTest extends TestCase
{
    private CurrentThemeProvider&MockObject $currentThemeProvider;
    private Packages&MockObject $assetPackages;
    private SubresourceIntegrityProvider&MockObject $subresourceIntegrityProvider;
    private FeatureChecker&MockObject $featureChecker;
    private EmbeddedFragmentJsMetadataProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->currentThemeProvider = $this->createMock(CurrentThemeProvider::class);
        $this->assetPackages = $this->createMock(Packages::class);
        $this->subresourceIntegrityProvider = $this->createMock(SubresourceIntegrityProvider::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->provider = new EmbeddedFragmentJsMetadataProvider(
            $this->currentThemeProvider,
            $this->assetPackages,
            $this->subresourceIntegrityProvider,
            $this->featureChecker
        );
    }

    public function testGetJsMetadataReturnsRuntimeUrlWithoutIntegrityWhenIntegrityDisabled(): void
    {
        $this->currentThemeProvider
            ->method('getCurrentThemeId')
            ->willReturn('intelligent_blue');
        $this->assetPackages
            ->method('getUrl')
            ->willReturnMap([
                ['/build/intelligent_blue/embedded_app.js', null, 'https://cdn.example.test/embedded_app.js?v=abc123'],
            ]);
        $this->featureChecker
            ->method('isFeatureEnabled')
            ->with('asset_subresource_integrity_enabled')
            ->willReturn(false);
        $this->subresourceIntegrityProvider
            ->expects(self::never())
            ->method('getHash');

        self::assertSame(
            [
                'runtimeUrl' => 'https://cdn.example.test/embedded_app.js?v=abc123',
                'runtimeIntegrity' => '',
            ],
            $this->provider->getJsMetadata()
        );
    }

    public function testGetJsMetadataReturnsRuntimeIntegrityWhenIntegrityEnabled(): void
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
        $this->subresourceIntegrityProvider
            ->expects(self::once())
            ->method('getHash')
            ->with('/build/intelligent_blue/embedded_app.js')
            ->willReturn('sha384-hash');

        self::assertSame(
            [
                'runtimeUrl' => '/build/intelligent_blue/embedded_app.js?v=abc123',
                'runtimeIntegrity' => 'sha384-hash',
            ],
            $this->provider->getJsMetadata()
        );
    }

    public function testGetJsMetadataReturnsEmptyIntegrityWhenRuntimeScriptHasNoHash(): void
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
        $this->subresourceIntegrityProvider
            ->expects(self::once())
            ->method('getHash')
            ->willReturn(null);

        self::assertSame('', $this->provider->getJsMetadata()['runtimeIntegrity']);
    }

    public function testGetJsMetadataThrowsExceptionWithoutCurrentTheme(): void
    {
        $this->currentThemeProvider
            ->method('getCurrentThemeId')
            ->willReturn(null);
        $this->featureChecker
            ->expects(self::never())
            ->method('isFeatureEnabled');
        $this->assetPackages
            ->expects(self::never())
            ->method('getUrl');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The embedded runtime cannot boot because there is no current theme.');

        $this->provider->getJsMetadata();
    }
}
