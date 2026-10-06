<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Tests\Unit\Provider;

use Oro\Bundle\StorefrontEmbeddedBundle\Provider\EmbeddedFragmentStylesMetadataProvider;
use Oro\Component\Layout\Extension\Theme\DataProvider\ThemeProvider;
use Oro\Component\Layout\Extension\Theme\Model\CurrentThemeProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Asset\Packages;

final class EmbeddedFragmentStylesMetadataProviderTest extends TestCase
{
    private ThemeProvider&MockObject $themeProvider;
    private CurrentThemeProvider&MockObject $currentThemeProvider;
    private Packages&MockObject $assetPackages;
    private EmbeddedFragmentStylesMetadataProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->themeProvider = $this->createMock(ThemeProvider::class);
        $this->currentThemeProvider = $this->createMock(CurrentThemeProvider::class);
        $this->assetPackages = $this->createMock(Packages::class);

        $this->provider = new EmbeddedFragmentStylesMetadataProvider(
            $this->themeProvider,
            $this->currentThemeProvider,
            $this->assetPackages
        );
    }

    public function testGetStylesMetadataReturnsRootRelativeUrlsOfCurrentThemeStyles(): void
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

        self::assertSame(
            [
                'styles' => [
                    '/build/intelligent_blue/css/critical.css?v=abc123',
                    '/build/intelligent_blue/css/styles.css?v=abc123',
                ],
                'printStyles' => ['/build/intelligent_blue/css/print.css?v=abc123'],
            ],
            $this->provider->getStylesMetadata()
        );
    }

    public function testGetStylesMetadataThrowsExceptionWithoutCurrentTheme(): void
    {
        $this->currentThemeProvider
            ->method('getCurrentThemeId')
            ->willReturn(null);
        $this->themeProvider
            ->expects(self::never())
            ->method('getStylesOutput');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The embedded runtime cannot boot because there is no current theme.');

        $this->provider->getStylesMetadata();
    }
}
