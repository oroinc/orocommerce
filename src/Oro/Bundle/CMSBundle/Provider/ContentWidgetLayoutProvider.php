<?php

namespace Oro\Bundle\CMSBundle\Provider;

use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Provides configuration of widgets defined for a particular layout theme.
 */
class ContentWidgetLayoutProvider
{
    private const WIDGETS_CACHE_KEY = 'oro_cms.provider.content_widget_layout';

    private ThemeManager $themeManager;
    private CacheInterface $cache;

    public function __construct(ThemeManager $themeManager, CacheInterface $cache)
    {
        $this->themeManager = $themeManager;
        $this->cache = $cache;
    }

    public function getWidgetLayouts(string $widgetType): array
    {
        $widgets = $this->cache->get(self::WIDGETS_CACHE_KEY, function () {
            return $this->collectWidgets();
        });

        return $widgets['layouts'][$widgetType] ?? [];
    }

    public function getWidgetLayoutLabel(string $widgetType, string $layout): string
    {
        $layouts = $this->getWidgetLayouts($widgetType);

        return $layouts[$layout] ?? $layout;
    }

    private function collectWidgets(): array
    {
        $widgets = [[]];

        foreach ($this->themeManager->getAllThemes() as $theme) {
            $themeConfig = $theme->getConfig();

            $widgets[] = $themeConfig['widgets'] ?? [];
        }

        return array_merge_recursive(...$widgets);
    }
}
