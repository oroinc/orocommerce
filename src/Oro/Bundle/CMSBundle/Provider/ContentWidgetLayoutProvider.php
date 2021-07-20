<?php

namespace Oro\Bundle\CMSBundle\Provider;

use Doctrine\Common\Cache\Cache;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;

/**
 * Provides configuration of widgets defined for a particular layout theme.
 */
class ContentWidgetLayoutProvider
{
    /** @var string */
    private const WIDGETS_CACHE_KEY = 'oro_cms.provider.content_widget_layout';

    /** @var ThemeManager */
    private $themeManager;

    /** @var Cache */
    private $cache;

    public function __construct(ThemeManager $themeManager, Cache $cache)
    {
        $this->themeManager = $themeManager;
        $this->cache = $cache;
    }

    public function getWidgetLayouts(string $widgetType): array
    {
        $widgets = $this->cache->fetch(self::WIDGETS_CACHE_KEY);
        if (false === $widgets) {
            $widgets = $this->collectWidgets();
            $this->cache->save(self::WIDGETS_CACHE_KEY, $widgets);
        }

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
