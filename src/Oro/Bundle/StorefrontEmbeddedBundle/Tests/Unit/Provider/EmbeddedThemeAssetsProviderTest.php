<?php

namespace Oro\Bundle\StorefrontEmbeddedBundle\Tests\Unit\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\StorefrontEmbeddedBundle\Provider\EmbeddedThemeAssetsProvider;
use Oro\Bundle\ThemeBundle\Provider\ThemeConfigurationProvider;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Provider\WebsiteProviderInterface;
use Oro\Component\Testing\TempDirExtension;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class EmbeddedThemeAssetsProviderTest extends TestCase
{
    use TempDirExtension;

    private WebsiteProviderInterface&MockObject $websiteProvider;
    private ThemeConfigurationProvider&MockObject $themeConfigurationProvider;
    private ConfigManager&MockObject $configManager;
    private Packages&MockObject $assetPackages;
    private string $publicDirectory;

    #[\Override]
    protected function setUp(): void
    {
        $this->websiteProvider = $this->createMock(WebsiteProviderInterface::class);
        $this->themeConfigurationProvider = $this->createMock(ThemeConfigurationProvider::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->assetPackages = $this->createMock(Packages::class);
        $this->publicDirectory = $this->getTempDir('embedded_theme_assets');
    }

    public function testListsTheBuiltFilesOfEveryThemeTheWebsitesRun(): void
    {
        $this->givenWebsitesRunning(['intelligent_blue', 'golden_carbon']);
        $this->givenFile('/build/intelligent_blue/embedded_app.js', '');
        $this->givenFile('/build/intelligent_blue/svg-icons/sprite.svg', '');
        $this->givenFile('/build/golden_carbon/embedded_app.js', '');
        $this->givenFile('/build/admin/app.js', '');

        self::assertSame(
            [
                '/build/golden_carbon/embedded_app.js',
                '/build/intelligent_blue/embedded_app.js',
                '/build/intelligent_blue/svg-icons/sprite.svg',
            ],
            $this->provider()->getFiles()
        );
    }

    public function testSkipsAThemeThatWasNeverBuilt(): void
    {
        $this->givenWebsitesRunning(['intelligent_blue', 'never_built']);
        $this->givenFile('/build/intelligent_blue/embedded_app.js', '');

        self::assertSame(['/build/intelligent_blue/embedded_app.js'], $this->provider()->getFiles());
    }

    public function testSkipsSourceMapsAndScssSidecars(): void
    {
        $this->givenWebsitesRunning(['intelligent_blue']);
        $this->givenFile('/build/intelligent_blue/embedded_app.js', '');
        $this->givenFile('/build/intelligent_blue/embedded_app.js.map', '');
        $this->givenFile('/build/intelligent_blue/css/styles.css', '');
        $this->givenFile('/build/intelligent_blue/css/styles.css.scss', '');
        $this->givenFile('/build/intelligent_blue/css/styles.scss.js', '');

        self::assertSame(
            ['/build/intelligent_blue/css/styles.css', '/build/intelligent_blue/embedded_app.js'],
            $this->provider()->getFiles()
        );
    }

    public function testAddsOnlyTheSharedFilesTheThemeStylesheetsReference(): void
    {
        $this->givenWebsitesRunning(['intelligent_blue']);
        $this->givenFile(
            '/build/intelligent_blue/css/styles.css',
            '@font-face{src:url(/build/_static/bundles/orofrontend/fonts/bitter.woff2)}'
            . '.loader{background:url("/build/_static/bundles/orofrontend/images/loader.svg?v=1")}'
        );
        $this->givenFile('/build/_static/bundles/orofrontend/fonts/bitter.woff2', '');
        $this->givenFile('/build/_static/bundles/orofrontend/images/loader.svg', '');
        $this->givenFile('/build/_static/bundles/orofrontend/images/never-referenced.svg', '');

        self::assertSame(
            [
                '/build/_static/bundles/orofrontend/fonts/bitter.woff2',
                '/build/_static/bundles/orofrontend/images/loader.svg',
                '/build/intelligent_blue/css/styles.css',
            ],
            $this->provider()->getFiles()
        );
    }

    public function testNamesASharedFileReferencedByTwoStylesheetsOnce(): void
    {
        $this->givenWebsitesRunning(['intelligent_blue']);
        $reference = 'url(/build/_static/bundles/orofrontend/fonts/bitter.woff2)';
        $this->givenFile('/build/intelligent_blue/css/styles.css', $reference);
        $this->givenFile('/build/intelligent_blue/css/critical.css', $reference);
        $this->givenFile('/build/_static/bundles/orofrontend/fonts/bitter.woff2', '');

        self::assertSame(
            [
                '/build/_static/bundles/orofrontend/fonts/bitter.woff2',
                '/build/intelligent_blue/css/critical.css',
                '/build/intelligent_blue/css/styles.css',
            ],
            $this->provider()->getFiles()
        );
    }

    public function testIgnoresAStylesheetReferenceThatLeavesTheBuildDirectory(): void
    {
        $this->givenWebsitesRunning(['intelligent_blue']);
        $this->givenFile(
            '/build/intelligent_blue/css/styles.css',
            'url(/build/_static/../../.env) url(/build/_static/bundles/missing.svg)'
        );
        $this->givenFile('/.env', 'APP_SECRET=1');

        self::assertSame(['/build/intelligent_blue/css/styles.css'], $this->provider()->getFiles());
    }

    private function provider(): EmbeddedThemeAssetsProvider
    {
        return new EmbeddedThemeAssetsProvider(
            $this->websiteProvider,
            $this->themeConfigurationProvider,
            $this->configManager,
            $this->assetPackages,
            new ArrayAdapter(),
            $this->publicDirectory
        );
    }

    private function givenWebsitesRunning(array $themeNames): void
    {
        $websites = [];
        $themeNameByWebsite = [];
        foreach ($themeNames as $themeName) {
            $website = new Website();
            $websites[] = $website;
            $themeNameByWebsite[spl_object_id($website)] = $themeName;
        }

        $this->websiteProvider->expects(self::atLeastOnce())
            ->method('getWebsites')
            ->willReturn($websites);

        $this->themeConfigurationProvider->expects(self::atLeastOnce())
            ->method('getThemeName')
            ->willReturnCallback(static fn ($website): string => $themeNameByWebsite[spl_object_id($website)]);
    }

    private function givenFile(string $path, string $content): void
    {
        $file = $this->publicDirectory . $path;
        if (!is_dir(\dirname($file))) {
            mkdir(\dirname($file), 0777, true);
        }

        file_put_contents($file, $content);
    }
}
