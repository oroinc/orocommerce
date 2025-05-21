<?php

declare(strict_types=1);

namespace Oro\Bundle\CMSBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\CMSBundle\Form\Extension\WYSIWYGTypeExtension;
use Oro\Bundle\CMSBundle\Form\Type\WYSIWYGType;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LayoutBundle\Provider\SvgIconsSupportProvider;
use Oro\Bundle\ThemeBundle\Provider\ThemeConfigurationProvider;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Oro\Component\Layout\Extension\Theme\DataProvider\ThemeProvider;
use Oro\Component\Layout\Extension\Theme\Model\Theme;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Asset\Packages;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class WYSIWYGTypeExtensionTest extends TestCase
{
    private ThemeManager&MockObject $themeManager;
    private ThemeProvider&MockObject $themeProvider;
    private ConfigManager&MockObject $configManager;
    private Packages&MockObject $packages;
    private ThemeConfigurationProvider&MockObject $themeConfigurationProvider;
    private SvgIconsSupportProvider&MockObject $svgIconsSupportProvider;
    private WYSIWYGTypeExtension $extension;
    private Website $defaultWebsite;

    #[\Override]
    protected function setUp(): void
    {
        $this->themeManager = $this->createMock(ThemeManager::class);
        $this->themeProvider = $this->createMock(ThemeProvider::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $websiteManager = $this->createMock(WebsiteManager::class);
        $this->packages = $this->createMock(Packages::class);
        $this->themeConfigurationProvider = $this->createMock(ThemeConfigurationProvider::class);
        $this->svgIconsSupportProvider = $this->createMock(SvgIconsSupportProvider::class);

        $this->extension = new WYSIWYGTypeExtension(
            $this->themeManager,
            $this->themeProvider,
            $this->configManager,
            $websiteManager,
            $this->packages,
            $this->themeConfigurationProvider,
            $this->svgIconsSupportProvider
        );

        $this->defaultWebsite = new Website();
        $websiteManager->expects(self::any())
            ->method('getDefaultWebsite')
            ->willReturn($this->defaultWebsite);
    }

    public function testGetExtendedTypes(): void
    {
        self::assertEquals([WYSIWYGType::class], WYSIWYGTypeExtension::getExtendedTypes());
    }

    public function testConfigureOptionsWithSingleTheme(): void
    {
        $themeName = 'theme1';
        $theme = new Theme($themeName);
        $themeLabel = 'Theme 1';
        $theme->setLabel($themeLabel);
        $themes = [$theme];

        $this->themeManager->expects(self::once())
            ->method('getEnabledThemes')
            ->with('commerce')
            ->willReturn($themes);

        $cssFilePath = 'styles/theme1.css';
        $criticalCssFilePath = 'styles/critical/theme1.css';
        $this->themeProvider->expects(self::exactly(2))
            ->method('getStylesOutput')
            ->withConsecutive([$themeName], [$themeName, 'critical_css'])
            ->willReturnOnConsecutiveCalls($cssFilePath, $criticalCssFilePath);

        $themeCssUrl = 'url_to_theme1.css';
        $criticalCssUrl = 'url_to_critical_theme1.css';
        $this->packages->expects(self::exactly(2))
            ->method('getUrl')
            ->withConsecutive([$criticalCssFilePath], [$cssFilePath])
            ->willReturnOnConsecutiveCalls($criticalCssUrl, $themeCssUrl);

        $isSvgIconsSupported = true;
        $this->themeManager->expects(self::once())
            ->method('getThemeOption')
            ->with($themeName, 'svg_icons_support')
            ->willReturn($isSvgIconsSupported);

        $this->themeConfigurationProvider->expects(self::once())
            ->method('getThemeName')
            ->with($this->defaultWebsite)
            ->willReturn($themeName);

        $resolver = new OptionsResolver();
        $this->extension->configureOptions($resolver);

        $options = $resolver->resolve();

        $expectedThemeData = [
            'name' => $themeName,
            'label' => $themeLabel,
            'stylesheet' => [$criticalCssUrl, $themeCssUrl],
            'svgIconsSupport' => $isSvgIconsSupported,
            'active' => true,
        ];

        self::assertIsArray($options['page-component']['options']['themes']);
        self::assertCount(1, $options['page-component']['options']['themes']);
        self::assertEquals($expectedThemeData, $options['page-component']['options']['themes'][0]);
    }

    public function testConfigureOptionsWithMultipleThemes(): void
    {
        $theme1Name = 'theme1';
        $theme1 = new Theme($theme1Name);
        $theme1Label = 'Theme 1';
        $theme1->setLabel($theme1Label);

        $theme2Name = 'theme2';
        $theme2 = new Theme($theme2Name);
        $theme2Label = 'Theme 2';
        $theme2->setLabel($theme2Label);

        $themes = [$theme1, $theme2];

        $this->themeManager->expects(self::once())
            ->method('getEnabledThemes')
            ->with('commerce')
            ->willReturn($themes);

        $theme1CssFilePath = 'styles/theme1.css';
        $theme2CssFilePath = 'styles/theme2.css';
        $theme1CriticalCssFilePath = 'styles/critical/theme1.css';
        $theme2CriticalCssFilePath = 'styles/critical/theme2.css';

        $this->themeProvider->expects(self::exactly(4))
            ->method('getStylesOutput')
            ->withConsecutive(
                [$theme1Name],
                [$theme1Name, 'critical_css'],
                [$theme2Name],
                [$theme2Name, 'critical_css']
            )
            ->willReturnOnConsecutiveCalls(
                $theme1CssFilePath,
                $theme1CriticalCssFilePath,
                $theme2CssFilePath,
                $theme2CriticalCssFilePath
            );

        $theme1CssUrl = 'url_to_theme1.css';
        $theme2CssUrl = 'url_to_theme2.css';
        $theme1CriticalCssUrl = 'url_to_critical_theme1.css';
        $theme2CriticalCssUrl = 'url_to_critical_theme2.css';

        $this->packages->expects(self::exactly(4))
            ->method('getUrl')
            ->withConsecutive(
                [$theme1CriticalCssFilePath],
                [$theme1CssFilePath],
                [$theme2CriticalCssFilePath],
                [$theme2CssFilePath]
            )
            ->willReturnOnConsecutiveCalls(
                $theme1CriticalCssUrl,
                $theme1CssUrl,
                $theme2CriticalCssUrl,
                $theme2CssUrl
            );

        $theme1SvgIconsSupported = true;
        $theme2SvgIconsSupported = false;

        $this->themeManager->expects(self::exactly(2))
            ->method('getThemeOption')
            ->withConsecutive([$theme1Name, 'svg_icons_support'], [$theme2Name, 'svg_icons_support'])
            ->willReturnOnConsecutiveCalls($theme1SvgIconsSupported, $theme2SvgIconsSupported);

        $this->themeConfigurationProvider->expects(self::once())
            ->method('getThemeName')
            ->with($this->defaultWebsite)
            ->willReturn($theme1Name);

        $resolver = new OptionsResolver();
        $this->extension->configureOptions($resolver);

        $options = $resolver->resolve();

        $expectedThemeData1 = [
            'name' => $theme1Name,
            'label' => $theme1Label,
            'stylesheet' => [$theme1CriticalCssUrl, $theme1CssUrl],
            'svgIconsSupport' => $theme1SvgIconsSupported,
            'active' => true,
        ];

        $expectedThemeData2 = [
            'name' => $theme2Name,
            'label' => $theme2Label,
            'stylesheet' => [$theme2CriticalCssUrl, $theme2CssUrl],
            'svgIconsSupport' => $theme2SvgIconsSupported,
            'active' => false,
        ];

        self::assertIsArray($options['page-component']['options']['themes']);
        self::assertCount(2, $options['page-component']['options']['themes']);
        self::assertEquals($expectedThemeData1, $options['page-component']['options']['themes'][0]);
        self::assertEquals($expectedThemeData2, $options['page-component']['options']['themes'][1]);
    }

    public function testConfigureOptionsWhenThemeConfigurationProviderReturnsNull(): void
    {
        $themeName = 'theme1';
        $theme = new Theme($themeName);
        $themeLabel = 'Theme 1';
        $theme->setLabel($themeLabel);
        $themes = [$theme];

        $this->themeManager->expects(self::once())
            ->method('getEnabledThemes')
            ->with('commerce')
            ->willReturn($themes);

        $cssFilePath = 'styles/theme1.css';
        $criticalCssFilePath = 'styles/critical/theme1.css';

        $this->themeProvider->expects(self::exactly(2))
            ->method('getStylesOutput')
            ->withConsecutive([$themeName], [$themeName, 'critical_css'])
            ->willReturnOnConsecutiveCalls($cssFilePath, $criticalCssFilePath);

        $themeCssUrl = 'url_to_theme1.css';
        $criticalCssUrl = 'url_to_critical_theme1.css';

        $this->packages->expects(self::exactly(2))
            ->method('getUrl')
            ->withConsecutive([$criticalCssFilePath], [$cssFilePath])
            ->willReturnOnConsecutiveCalls($criticalCssUrl, $themeCssUrl);

        $this->themeManager->expects(self::once())
            ->method('getThemeOption')
            ->with($themeName, 'svg_icons_support')
            ->willReturn(true);

        $this->themeConfigurationProvider->expects(self::once())
            ->method('getThemeName')
            ->with($this->defaultWebsite)
            ->willReturn(null);

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_frontend.frontend_theme', false, false, $this->defaultWebsite)
            ->willReturn($themeName);

        $resolver = new OptionsResolver();
        $this->extension->configureOptions($resolver);

        $options = $resolver->resolve();

        $expectedThemeData = [
            'name' => $themeName,
            'label' => $themeLabel,
            'stylesheet' => [$criticalCssUrl, $themeCssUrl],
            'svgIconsSupport' => true,
            'active' => true,
        ];

        self::assertIsArray($options['page-component']['options']['themes']);
        self::assertCount(1, $options['page-component']['options']['themes']);
        self::assertEquals($expectedThemeData, $options['page-component']['options']['themes'][0]);
    }

    public function testConfigureOptionsWithNoStyles(): void
    {
        $themeName = 'theme1';
        $theme = new Theme($themeName);
        $themeLabel = 'Theme 1';
        $theme->setLabel($themeLabel);
        $themes = [$theme];

        $this->themeManager->expects(self::once())
            ->method('getEnabledThemes')
            ->with('commerce')
            ->willReturn($themes);

        $this->themeProvider->expects(self::exactly(2))
            ->method('getStylesOutput')
            ->withConsecutive([$themeName], [$themeName, 'critical_css'])
            ->willReturnOnConsecutiveCalls(null, null);

        $this->packages->expects(self::never())->method('getUrl');

        $this->themeManager->expects(self::once())
            ->method('getThemeOption')
            ->with($themeName, 'svg_icons_support')
            ->willReturn(false);

        $this->themeConfigurationProvider->expects(self::once())
            ->method('getThemeName')
            ->with($this->defaultWebsite)
            ->willReturn($themeName);

        $resolver = new OptionsResolver();
        $this->extension->configureOptions($resolver);

        $options = $resolver->resolve();

        $expectedThemeData = [
            'name' => $themeName,
            'label' => $themeLabel,
            'stylesheet' => [],
            'svgIconsSupport' => false,
            'active' => true,
        ];

        self::assertIsArray($options['page-component']['options']['themes']);
        self::assertCount(1, $options['page-component']['options']['themes']);
        self::assertEquals($expectedThemeData, $options['page-component']['options']['themes'][0]);
    }

    public function testConfigureOptionsWithNonFirstActiveTheme(): void
    {
        $theme1Name = 'theme1';
        $theme1 = new Theme($theme1Name);
        $theme1Label = 'Theme 1';
        $theme1->setLabel($theme1Label);

        $theme2Name = 'theme2';
        $theme2 = new Theme($theme2Name);
        $theme2Label = 'Theme 2';
        $theme2->setLabel($theme2Label);

        $themes = [$theme1, $theme2];

        $this->themeManager->expects(self::once())
            ->method('getEnabledThemes')
            ->with('commerce')
            ->willReturn($themes);

        $this->themeProvider->expects(self::exactly(4))
            ->method('getStylesOutput')
            ->willReturnOnConsecutiveCalls(
                'styles/theme1.css',
                'styles/critical/theme1.css',
                'styles/theme2.css',
                'styles/critical/theme2.css'
            );

        $this->packages->expects(self::exactly(4))
            ->method('getUrl')
            ->willReturnOnConsecutiveCalls(
                'url_to_critical_theme1.css',
                'url_to_theme1.css',
                'url_to_critical_theme2.css',
                'url_to_theme2.css'
            );

        $this->themeManager->expects(self::exactly(2))
            ->method('getThemeOption')
            ->withConsecutive([$theme1Name], [$theme2Name])
            ->willReturnOnConsecutiveCalls(false, true);

        $this->themeConfigurationProvider->expects(self::once())
            ->method('getThemeName')
            ->with($this->defaultWebsite)
            ->willReturn($theme2Name);

        $resolver = new OptionsResolver();
        $this->extension->configureOptions($resolver);

        $options = $resolver->resolve();

        $expectedThemeData1 = [
            'name' => $theme1Name,
            'label' => $theme1Label,
            'stylesheet' => ['url_to_critical_theme1.css', 'url_to_theme1.css'],
            'svgIconsSupport' => false,
            'active' => false,
        ];

        $expectedThemeData2 = [
            'name' => $theme2Name,
            'label' => $theme2Label,
            'stylesheet' => ['url_to_critical_theme2.css', 'url_to_theme2.css'],
            'svgIconsSupport' => true,
            'active' => true,
        ];

        self::assertIsArray($options['page-component']['options']['themes']);
        self::assertCount(2, $options['page-component']['options']['themes']);
        self::assertEquals($expectedThemeData1, $options['page-component']['options']['themes'][0]);
        self::assertEquals($expectedThemeData2, $options['page-component']['options']['themes'][1]);
    }
}
