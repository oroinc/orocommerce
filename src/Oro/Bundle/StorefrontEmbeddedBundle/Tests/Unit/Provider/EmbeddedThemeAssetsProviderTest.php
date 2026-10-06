<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Tests\Unit\Provider;

use Oro\Bundle\StorefrontEmbeddedBundle\Provider\EmbeddedThemeAssetsProvider;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use Oro\Component\Layout\Extension\Theme\Model\CurrentThemeProvider;
use Oro\Component\Testing\TempDirExtension;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Filesystem\Filesystem;

final class EmbeddedThemeAssetsProviderTest extends TestCase
{
    use LoggerAwareTraitTestTrait;
    use TempDirExtension;

    private CurrentThemeProvider&MockObject $currentThemeProvider;
    private Packages&MockObject $assetPackages;
    private string $publicDirectory;
    private Filesystem $filesystem;
    private EmbeddedThemeAssetsProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->currentThemeProvider = $this->createMock(CurrentThemeProvider::class);
        $this->assetPackages = $this->createMock(Packages::class);
        $this->publicDirectory = $this->getTempDir('embedded_theme_assets');
        $this->filesystem = new Filesystem();

        $this->provider = new EmbeddedThemeAssetsProvider(
            $this->currentThemeProvider,
            $this->assetPackages,
            new ArrayAdapter(),
            $this->publicDirectory
        );
        $this->setUpLoggerMock($this->provider);
    }

    public function testListsTheBuiltFilesOfTheCurrentTheme(): void
    {
        $this->currentThemeProvider
            ->expects(self::once())
            ->method('getCurrentThemeId')
            ->willReturn('intelligent_blue');
        $this->filesystem->dumpFile($this->publicDirectory . '/build/intelligent_blue/embedded_app.js', '');
        $this->filesystem->dumpFile($this->publicDirectory . '/build/intelligent_blue/svg-icons/sprite.svg', '');
        $this->filesystem->dumpFile($this->publicDirectory . '/build/admin/app.js', '');

        self::assertSame(
            [
                '/build/intelligent_blue/embedded_app.js',
                '/build/intelligent_blue/svg-icons/sprite.svg',
            ],
            $this->provider->getFiles()
        );
    }

    public function testListsNothingWhenNoThemeResolves(): void
    {
        $this->currentThemeProvider
            ->expects(self::once())
            ->method('getCurrentThemeId')
            ->willReturn(null);
        $this->filesystem->dumpFile($this->publicDirectory . '/build/intelligent_blue/embedded_app.js', '');

        $this->assetPackages

            ->expects(self::never())
            ->method('getVersion');

        self::assertSame([], $this->provider->getFiles());
    }

    public function testKeepsASeparateCacheEntryForEachTheme(): void
    {
        $this->currentThemeProvider
            ->expects(self::exactly(2))
            ->method('getCurrentThemeId')
            ->willReturnOnConsecutiveCalls('intelligent_blue', 'golden_carbon');
        $this->filesystem->dumpFile($this->publicDirectory . '/build/intelligent_blue/embedded_app.js', '');
        $this->filesystem->dumpFile($this->publicDirectory . '/build/golden_carbon/embedded_app.js', '');

        self::assertSame(['/build/intelligent_blue/embedded_app.js'], $this->provider->getFiles());
        self::assertSame(['/build/golden_carbon/embedded_app.js'], $this->provider->getFiles());
    }

    public function testSkipsAThemeThatWasNeverBuilt(): void
    {
        $this->currentThemeProvider
            ->expects(self::once())
            ->method('getCurrentThemeId')
            ->willReturn('never_built');
        $this->filesystem->dumpFile($this->publicDirectory . '/build/intelligent_blue/embedded_app.js', '');

        $this->loggerMock
            ->expects(self::once())
            ->method('warning')
            ->with(
                'Theme build directory is missing, its files were not listed.',
                ['theme_directory' => $this->publicDirectory . '/build/never_built']
            );

        self::assertSame([], $this->provider->getFiles());
    }

    public function testSkipsSourceMapsAndScssSidecars(): void
    {
        $this->currentThemeProvider
            ->expects(self::once())
            ->method('getCurrentThemeId')
            ->willReturn('intelligent_blue');
        $this->filesystem->dumpFile($this->publicDirectory . '/build/intelligent_blue/embedded_app.js', '');
        $this->filesystem->dumpFile($this->publicDirectory . '/build/intelligent_blue/embedded_app.js.map', '');
        $this->filesystem->dumpFile($this->publicDirectory . '/build/intelligent_blue/css/styles.css', '');
        $this->filesystem->dumpFile($this->publicDirectory . '/build/intelligent_blue/css/styles.css.scss', '');
        $this->filesystem->dumpFile($this->publicDirectory . '/build/intelligent_blue/css/styles.scss.js', '');

        self::assertSame(
            ['/build/intelligent_blue/css/styles.css', '/build/intelligent_blue/embedded_app.js'],
            $this->provider->getFiles()
        );
    }

    public function testAddsOnlyTheSharedFilesTheThemeStylesheetsReference(): void
    {
        $this->currentThemeProvider
            ->expects(self::once())
            ->method('getCurrentThemeId')
            ->willReturn('intelligent_blue');
        $this->filesystem->dumpFile(
            $this->publicDirectory . '/build/intelligent_blue/css/styles.css',
            '@font-face{src:url(/build/_static/bundles/orofrontend/fonts/bitter.woff2)}'
            . '.loader{background:url("/build/_static/bundles/orofrontend/images/loader.svg?v=1")}'
        );
        $this->filesystem->dumpFile(
            $this->publicDirectory . '/build/_static/bundles/orofrontend/fonts/bitter.woff2',
            ''
        );
        $this->filesystem->dumpFile(
            $this->publicDirectory . '/build/_static/bundles/orofrontend/images/loader.svg',
            ''
        );
        $this->filesystem->dumpFile(
            $this->publicDirectory . '/build/_static/bundles/orofrontend/images/never-referenced.svg',
            ''
        );

        self::assertSame(
            [
                '/build/_static/bundles/orofrontend/fonts/bitter.woff2',
                '/build/_static/bundles/orofrontend/images/loader.svg',
                '/build/intelligent_blue/css/styles.css',
            ],
            $this->provider->getFiles()
        );
    }

    public function testNamesASharedFileReferencedByTwoStylesheetsOnce(): void
    {
        $this->currentThemeProvider
            ->expects(self::once())
            ->method('getCurrentThemeId')
            ->willReturn('intelligent_blue');
        $reference = 'url(/build/_static/bundles/orofrontend/fonts/bitter.woff2)';
        $this->filesystem->dumpFile($this->publicDirectory . '/build/intelligent_blue/css/styles.css', $reference);
        $this->filesystem->dumpFile($this->publicDirectory . '/build/intelligent_blue/css/critical.css', $reference);
        $this->filesystem->dumpFile(
            $this->publicDirectory . '/build/_static/bundles/orofrontend/fonts/bitter.woff2',
            ''
        );

        self::assertSame(
            [
                '/build/_static/bundles/orofrontend/fonts/bitter.woff2',
                '/build/intelligent_blue/css/critical.css',
                '/build/intelligent_blue/css/styles.css',
            ],
            $this->provider->getFiles()
        );
    }

    public function testLogsAndSkipsAThemeStylesheetThatCannotBeRead(): void
    {
        $this->currentThemeProvider
            ->expects(self::once())
            ->method('getCurrentThemeId')
            ->willReturn('intelligent_blue');
        $this->filesystem->dumpFile(
            $this->publicDirectory . '/build/intelligent_blue/css/styles.css',
            'url(/build/_static/bundles/orofrontend/fonts/bitter.woff2)'
        );
        $this->filesystem->dumpFile(
            $this->publicDirectory . '/build/_static/bundles/orofrontend/fonts/bitter.woff2',
            ''
        );
        $this->filesystem->chmod($this->publicDirectory . '/build/intelligent_blue/css/styles.css', 0000);

        $this->loggerMock
            ->expects(self::once())
            ->method('error')
            ->with(
                'Theme stylesheet could not be read.',
                ['stylesheet' => '/build/intelligent_blue/css/styles.css']
            );

        self::assertSame(['/build/intelligent_blue/css/styles.css'], $this->provider->getFiles());
    }

    public function testIgnoresAStylesheetReferenceThatLeavesTheBuildDirectory(): void
    {
        $this->currentThemeProvider
            ->expects(self::once())
            ->method('getCurrentThemeId')
            ->willReturn('intelligent_blue');
        $this->filesystem->dumpFile(
            $this->publicDirectory . '/build/intelligent_blue/css/styles.css',
            'url(/build/_static/../../.env) url(/build/_static/bundles/missing.svg)'
        );
        $this->filesystem->dumpFile($this->publicDirectory . '/.env', 'APP_SECRET=1');

        self::assertSame(['/build/intelligent_blue/css/styles.css'], $this->provider->getFiles());
    }
}
