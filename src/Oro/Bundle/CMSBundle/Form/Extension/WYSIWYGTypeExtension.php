<?php

namespace Oro\Bundle\CMSBundle\Form\Extension;

use Oro\Bundle\CMSBundle\Form\Type\WYSIWYGType;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LayoutBundle\Provider\SvgIconsSupportProvider;
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
    private const COMMERCE_GROUP = 'commerce';

    public function __construct(
        private ThemeManager $themeManager,
        private ThemeProvider $themeProvider,
        private ConfigManager $configManager,
        private WebsiteManager $websiteManager,
        private Packages $packages,
        private ThemeConfigurationProvider $themeConfigurationProvider,
        private SvgIconsSupportProvider $svgIconsSupportProvider
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public static function getExtendedTypes(): iterable
    {
        return [WYSIWYGType::class];
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
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
        $themes = $this->themeManager->getEnabledThemes(self::COMMERCE_GROUP);
        $layoutThemeName = $this->getThemeName();

        $themesData = [];
        foreach ($themes as $theme) {
            $themeName = $theme->getName();
            $styleOutput = $this->themeProvider->getStylesOutput($themeName);
            $themeData = [
                'name' => $themeName,
                'label' => $theme->getLabel(),
                'stylesheet' => $styleOutput ? $this->packages->getUrl($styleOutput) : '',
                'svgIconsSupport' => $this->svgIconsSupportProvider->isSvgIconsSupported($themeName),
            ];
            if ($layoutThemeName === $themeName) {
                $themeData['active'] = true;
            }

            $themesData[] = $themeData;
        }

        return $themesData;
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
