<?php

namespace Oro\Bundle\CMSBundle\Form\Extension;

use Oro\Bundle\CMSBundle\Form\Type\WYSIWYGType;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ThemeBundle\Provider\ThemeConfigurationProvider;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Oro\Component\Layout\Extension\Theme\DataProvider\ThemeProvider;
use Oro\Component\Layout\Extension\Theme\Model\Theme;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Adds information about layout themes to the WYSIWYGType options.
 */
class WYSIWYGTypeExtension extends AbstractTypeExtension
{
    private const string COMMERCE_GROUP = 'commerce';

    public function __construct(
        private ThemeManager $themeManager,
        private ThemeProvider $themeProvider,
        private ConfigManager $configManager,
        private WebsiteManager $websiteManager,
        private Packages $packages,
        private ThemeConfigurationProvider $themeConfigurationProvider
    ) {
    }

    #[\Override]
    public static function getExtendedTypes(): iterable
    {
        return [WYSIWYGType::class];
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('page-component', function (Options $options, $value) {
            $value['options']['themes'] = $this->getThemes();

            return $value;
        });
    }

    /**
     * @return Theme[]
     */
    private function getThemes(): array
    {
        $enabledThemes = $this->themeManager->getEnabledThemes(self::COMMERCE_GROUP);
        $currentThemeName = $this->getThemeName();

        return array_map(
            fn ($theme) => $this->buildThemeData($theme, $currentThemeName),
            $enabledThemes
        );
    }

    private function buildThemeData(object $theme, string $currentThemeName): array
    {
        $themeName = $theme->getName();
        $styles = $this->collectThemeStyles($themeName);

        return [
            'name' => $themeName,
            'label' => $theme->getLabel(),
            'stylesheet' => $styles ?: [],
            'svgIconsSupport' => $this->themeManager->getThemeOption($themeName, 'svg_icons_support') ?? false,
            'active' => $themeName === $currentThemeName,
        ];
    }

    private function collectThemeStyles(string $themeName): array
    {
        $styleOutput = $this->themeProvider->getStylesOutput($themeName);
        $styleCritical = $this->themeProvider->getStylesOutput($themeName, 'critical_css');

        return array_values(array_filter([
            $styleCritical ? $this->packages->getUrl($styleCritical) : null,
            $styleOutput ? $this->packages->getUrl($styleOutput) : null,
        ]));
    }

    private function getThemeName(): ?string
    {
        $defaultWebsite = $this->websiteManager->getDefaultWebsite();
        $themeName = $this->themeConfigurationProvider->getThemeName($defaultWebsite);
        if ($themeName) {
            return $themeName;
        }

        return $this->configManager->get('oro_frontend.frontend_theme', false, false, $defaultWebsite);
    }
}
